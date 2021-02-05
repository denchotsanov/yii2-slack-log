<?php
namespace denchotsanov\slacklog;

use yii\base\InvalidValueException;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\log\Logger;

/**
 * This class implements logging to Slack channels via incoming webhooks.
 */
class Slack extends \yii\log\Target
{
    /**
     * @var Client|array|string Yii HTTP client configuration.
     * This can be a component ID, a configuration array or a Client instance.
     * @since 1.2
     */
    public $httpClient = [
        'class' => 'yii\httpclient\Client',
    ];

    /**
     * @var string incoming webhook URL.
     */
    public $webhookUrl;

    /**
     * @var string displayed username.
     */
    public $username;

    /**
     * @var string icon URL.
     */
    public $iconUrl;

    /**
     * @var string icon emoji.
     */
    public $iconEmoji;

    /**
     * @var string channel or direct message name.
     */
    public $channel;

    /**
     * @var string[] colors per a logger level.
     */
    public $colors = [
        Logger::LEVEL_ERROR => 'danger',
        Logger::LEVEL_WARNING => 'warning',
    ];

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        $this->exportInterval = 20;
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->httpClient = Instance::ensure($this->httpClient, Client::className());
    }

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidValueException
     */
    public function export()
    {
        $response = $this->httpClient
            ->post($this->webhookUrl, $this->getPayload())
            ->setFormat(Client::FORMAT_JSON)
            ->send();
        if (!$response->getIsOk()) {
            throw new InvalidValueException(
                'Unable to send logs to Slack: ' . $response->getContent(), (int) $response->getStatusCode()
            );
        }
    }

    /**
     * Encodes special chars in a string as HTML entities.
     * @param string $string
     * @return string
     * @since 1.3
     */
    protected function encode($string)
    {
        return htmlspecialchars($string, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * Returns a Slack API payload.
     * @return array payload.
     * @since 1.2
     */
    protected function getPayload()
    {
        $payload = [
            'parse' => 'none',
            'attachments' => array_map([$this, 'formatMessageAttachment'], $this->messages),
        ];
        $this
            ->insertIntoPayload($payload, 'username', $this->username)
            ->insertIntoPayload($payload, 'icon_url', $this->iconUrl)
            ->insertIntoPayload($payload, 'icon_emoji', $this->iconEmoji)
            ->insertIntoPayload($payload, 'channel', $this->channel);
        return $payload;
    }

    /**
     * Returns a properly formatted message attachment for Slack API.
     * @param array $message raw message.
     * @return array Slack message attachment.
     */
    protected function formatMessageAttachment($message)
    {
        $message = new Message($message, $this);
        $attachment = [
            'fallback' => $this->encode($this->formatMessage($message->message)),
            'title' => ucwords($message->getLevel()),
            'fields' => [],
            'text' => "```\n" . $this->encode($message->getText()) . "\n```",
            'footer' => \Yii::$app->id,
            'ts' => (int) round($message->getTimestamp()),
            'mrkdwn_in' => [
                'fields',
                'text',
            ],
        ];
        if ($message->getIsConsoleRequest()) {
            $attachment['author_name'] = $message->getCommandLine();
        } else {
            $attachment['author_name'] = $attachment['author_link'] = $message->getUrl();
        }
        if (isset($this->colors[$message->message[1]])) {
            $attachment['color'] = $this->colors[$message->message[1]];
        }
        $this
            ->insertField($attachment, 'Level', $message->getLevel(), true, false)
            ->insertField($attachment, 'Category', $message->getCategory(), true)
            ->insertField($attachment, 'Prefix', $message->getPrefix(), true)
            ->insertField($attachment, 'User IP', $message->getUserIp(), true, false)
            ->insertField($attachment, 'User ID', $message->getUserId(), true, false)
            ->insertField($attachment, 'Session ID', $message->getSessionId(), true, false)
            ->insertField($attachment, 'Stack Trace', $message->getStackTrace(), false);
        return $attachment;
    }

    /**
     * Inserts the new attachment field if the value is not empty.
     * @param array $attachment
     * @param string $title
     * @param string|null $value
     * @param bool $short
     * @param bool $wrapAsCode
     * @return $this
     */
    private function insertField(array &$attachment, $title, $value, $short, $wrapAsCode = true)
    {
        if ((string) $value === '') {
            return $this;
        }

        $value = $this->encode($value);
        if ($wrapAsCode) {
            if ($short) {
                $value = '`' . $value . '`';
            } else {
                $value = "```\n" . $value . "\n```";
            }
        }
        $attachment['fields'][] = [
            'title' => $title,
            'value' => $value,
            'short' => $short,
        ];
        return $this;
    }

    /**
     * Copies the value to the payload if the value is set.
     * @param array $payload
     * @param string $name
     * @param string $value
     * @return $this
     */
    private function insertIntoPayload(array &$payload, $name, $value)
    {
        if ((string) $value !== '') {
            $payload[$name] = $value;
        }
        return $this;
    }
}
