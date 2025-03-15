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
namespace Upp\Exceptions\Handler;

use Hyperf\RateLimit\Exception\RateLimitException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Upp\Exceptions\AppException;
use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;


class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    public function __construct(StdoutLoggerInterface $logger, FormatterInterface $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {

        // 判断被捕获到的异常是希望被捕获的异常
        if ($throwable instanceof AppException) {

            // 格式化输出
            $data = json_encode([
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            // 阻止异常冒泡
            //$this->stopPropagation();
            return $response->withStatus(200)->withBody(new SwooleStream($data));

        }
        
        if ($throwable instanceof RateLimitException) {
             // 格式化输出
            $data = json_encode([
                'code' => 400,
                'message' => "限流"
            ], JSON_UNESCAPED_UNICODE);
            // 阻止异常冒泡
            //$this->stopPropagation();
            return $response->withStatus(200)->withBody(new SwooleStream($data));
            
        }
        
        if ($throwable instanceof HttpException){

            $data = json_encode([
                'code' => 400,
                'message' => $throwable->getMessage(),
                'data' => [
                    //'line'=>$throwable->getLine(),
                    //'file' => $throwable->getFile()
                ]
            ], JSON_UNESCAPED_UNICODE);
            //$this->stopPropagation();
            // 阻止异常冒泡
            return $response->withStatus(200)->withBody(new SwooleStream($data));
        }
        
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getCode(),$throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        //$this->logger->error($throwable->getTraceAsString());
        // 交给下一个异常处理器
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {

        //return $throwable instanceof HttpException;
        return true;
    }
}
