<?php

namespace Acquia\Hmac\Guzzle;

use Acquia\Hmac\RequestSignerInterface;
use Acquia\Hmac\Request\Guzzle as RequestWrapper;
use Psr\Http\Message\RequestInterface;

class HmacAuthMiddleware
{
    /**
     * @var \Acquia\Hmac\RequestSignerInterface
     */
    protected $requestSigner;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $defaultContentType = 'application/json; charset=utf-8';

    /**
     * @param \Acquia\Hmac\RequestSignerInterface $requestSigner
     * @param string $id
     * @param string $secretKey
     */
    public function __construct(RequestSignerInterface $requestSigner, $id, $secretKey)
    {
        $this->requestSigner = $requestSigner;
        $this->id            = $id;
        $this->secretKey     = $secretKey;
    }

    /**
     * @var string $contentType
     */
    public function setDefaultContentType($contentType)
    {
        $this->defaultContentType = $contentType;
    }

    /**
     * @return string
     */
    public function getDefaultContentType()
    {
        return $this->defaultContentType;
    }

    /**
     * Called when the middleware is handled.
     *
     * @param callable $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {

            $request = $this->onBefore($request);

            return $handler($request, $options);
        };
    }

    private function onBefore(RequestInterface $request)
    {
        $requestWrapper = new RequestWrapper($request);

        if (!$request->hasHeader('Date')) {
            $time = new \DateTime();
            $time->setTimezone(new \DateTimeZone('GMT'));
            $request->setHeader('Date', $time->format('D, d M Y H:i:s \G\M\T'));
        }

        if (!$request->hasHeader('Content-Type')) {
            $request->setHeader('Content-Type', $this->defaultContentType);
        }

        $authorization = $this->requestSigner->getAuthorization($requestWrapper, $this->id, $this->secretKey);
        $request->setHeader('Authorization', $authorization);

        return $request;
    }
}