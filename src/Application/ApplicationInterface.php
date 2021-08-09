<?php
namespace Swift\Framework\Application;

use Throwable;

/**
 * Interface ApplicationInterface
 * @package Swift\Framework\Application
 */
interface ApplicationInterface
{
    /**
     * 运行应用
     * @return mixed
     */
    public function run();

    /**
     * 错误处理
     * @param int $type 错误类型
     * @param string $message 消息
     * @param string|null $file 所在文件
     * @param int|null $line 所在行
     * @param array|null $context 上下文
     */
    public function errorHandler(int $type,
                                 string $message,
                                 string $file = null,
                                 int $line = null,
                                 array $context = null);

    /**
     * 异常处理
     * @param Throwable $exception
     */
    public function exceptionHandler(Throwable $exception);
}
