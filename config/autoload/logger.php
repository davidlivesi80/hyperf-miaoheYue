<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'default' => [//默认
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'task' => [//运行异常
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/task.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'error' => [//运行异常
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/error.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'robot' => [//挖矿日志
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/robot.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'other' => [//其他
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/other.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'kline' => [//控K
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/kline.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'team' => [//其他
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/team.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'second' => [//下单合约
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,//日期轮询
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/second.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ]
];
