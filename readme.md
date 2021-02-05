# Slack log target for Yii 2

## Installation

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Run
```bash
composer require "denchotsanov/yii2-slack-log:^1.0"
```
or add
```json
"denchotsanov/yii2-slack-log": "^1.0"
```
to the require section of your `composer.json` file.

## Usage

First set up an [incoming webhook integration](https://my.slack.com/services/new/incoming-webhook/) in your Slack team and obtain a token. It should look like `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX`.

Then set the following Yii 2 configuration parameters:

```php
'components' => [
    'log' => [
        'targets' => [
            [
                'class' => 'denchotsanov\slacklog\Slack',
                'webhookUrl' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
            ],
        ],
    ],
],
```
Sample config:
```php
'components' => [
    'log' => [
        'targets' => [
            [
                'class' => 'denchotsanov\slacklog\Slack',
                'levels' => ['error'],
                'except' => [
                    'yii\web\HttpException:*',
                ],
                'enabled' => YII_ENV_PROD,
                'webhookUrl' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
                'username' => 'Alarm Bot',
                'iconEmoji' => ':poop:',
            ],
        ],
    ],
],
```
