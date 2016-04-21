<?php
namespace SmallDuck;

/**
 * Curl请求类
 * @author 周仕林<875199116@qq.com> 2016-04-17
 */
class Curl
{
    /**
     * 默认超时时间
     */
    const DEFAULT_TIMEOUT = 10;

    /**
     * Curl句柄
     * @var null|resource
     */
    public $curl = null;

    //Curl请求错误
    public $curlError = false;
    public $curlErrorCode = 0;
    public $curlErrorMessage = null;

    //http请求错误
    public $httpError = false;
    public $httpStatusCode = 0;
    public $httpErrorMessage = '';

    public $error = false;
    public $errorMessage = '';

    //响应数据
    public $rawResponse = null;
    public $rawResponseHeaders = null;
    public $response = null;
    public $responseHeaders = null;

    //响应Cookie数据
    public $responseCookies = array();

    public $url = null;
    public $baseUrl = null;

    public $options = array();

    public $contentTypeJson = 'application/json;charset=UTF-8';

    public $headers = array();

    /**
     * Curl constructor
     * @param null $url URL
     * @throws \RuntimeException
     */
    public function __construct($url = null)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL library is not loaded');
        }

        $this->curl = curl_init();
        $this->setDefaultUserAgent();
        $this->setDefaultTimeout();
        $this->setOption(CURLINFO_HEADER_OUT, true);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_HEADERFUNCTION, array($this, 'headerCallback'));
        $this->setUrl($url);
    }

    /**
     * 设置默认User Agent
     */
    public function setDefaultUserAgent()
    {
        $this->setUserAgent('SmallDuck Curl');
    }

    /**
     * 设置默认超时时间
     */
    public function setDefaultTimeout()
    {
        $this->setTimeout(self::DEFAULT_TIMEOUT);
    }

    /**
     * 设置Curl选项
     * @param string $option 选项名称
     * @param string $value 值
     * @return bool
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * 设置请求头信息
     * @param string $key key
     * @param string $value value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        $headers = array();
        foreach ($this->headers as $k => $v) {
            $headers[] = $k . ":" .$v;
        }

        $this->setOption(CURLOPT_HEADER, $headers);
    }

    /**
     * 设置请求URL
     * @param string $url URL地址
     * @param array $data 请求数据
     */
    public function setURL($url, $data = array())
    {
        $this->baseUrl = $url;
        $this->url = $this->buildURL($url, $data);
        $this->setOption(CURLOPT_URL, $this->url);
    }

    /**
     * 构造URL链接
     * @param string $url URL地址
     * @param array $data get请求数据
     * @return string
     */
    public function buildURL($url, $data = array())
    {
        return $url . (empty($data) ? '' : http_build_query($data));
    }

    /**
     * 设置超时时间
     * @param int $seconds 秒
     */
    public function setTimeout($seconds)
    {
        $this->setOption(CURLOPT_TIMEOUT, $seconds);
    }

    /**
     * 设置User Agent
     * @param $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->setOption(CURLOPT_USERAGENT, $userAgent);
    }

    /**
     * 执行Curl请求
     */
    public function exec()
    {
        $this->rawResponse = curl_exec($this->curl);
        $this->curlErrorCode = curl_errno($this->curl);
        $this->curlErrorMessage = curl_error($this->curl);
        $this->curlError = !($this->curlErrorCode === 0);

        $this->httpStatusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->httpError = $this->httpStatusCode >= 400 && $this->httpStatusCode < 600;
        $this->responseHeaders = $this->parseResponseHeader($this->rawResponseHeaders);
        list($this->response, $this->rawResponse) = $this->parseResponse($this->responseHeaders, $this->rawResponse);

        $this->error = $this->curlError || $this->httpError;
        if ($this->error) {
            isset($this->responseHeaders['Status-Line']) && $this->httpErrorMessage = $this->responseHeaders['Status-Line'];
        }

        $this->errorMessage = $this->curlError ? $this->curlErrorMessage : $this->httpErrorMessage;

        //TODO 可以定于钩子函数，比如错误函数

        return $this->response;
    }

    /**
     * 解析Response Header
     * @param string $rawResponseHeader 响应头字符串
     * @return array
     */
    public function parseResponseHeader($rawResponseHeader)
    {
        $rawHeaders = preg_split('/\r\n/', $rawResponseHeader, null, PREG_SPLIT_NO_EMPTY);
        $rawHeadersCount = count($rawHeaders);
        $httpHeaders = array();
        $httpHeaders['Status-Line'] = isset($rawHeaders[0]) ? $rawHeaders[0] : '';

        for($i = 1; $i < $rawHeadersCount; $i++) {
            list($key, $value) = explode(':', $rawHeaders[$i]);
            $key = trim($key);
            $value = trim($value);

            if (isset($httpHeaders[$key])) {
                $httpHeaders[$key] .= ',' . $value;
            } else {
                $httpHeaders[$key] = $value;
            }
        }

        return $httpHeaders;
    }

    /**
     * 解析数据
     * @param array $responseHeaders 响应头信息
     * @param string $rawResponse 响应返回数据
     * @return array
     */
    public function parseResponse($responseHeaders, $rawResponse)
    {
        $response = $rawResponse;
        if (isset($responseHeaders['Content-Type'])) {
            if ($responseHeaders['Content-Type'] == $this->contentTypeJson) {
                $response = json_decode($response, true);
            }
        }

        return array($response, $rawResponse);
    }

    /**
     * 获取Curl Option
     * @param string $option Option索引值
     * @return null
     */
    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * 获取所有的Curl Option
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * 根据key值获取Cookie值
     * @param string $key Cookie名
     * @return null
     */
    public function getCookie($key)
    {
        return $this->getResponseCookie($key);
    }

    /**
     * 根据key值获取响应Cookie值
     * @param string $key Cookie名
     * @return null
     */
    public function getResponseCookie($key)
    {
        return isset($this->responseCookies[$key]) ? $this->responseCookies[$key] : null;
    }

    /**
     * 获取所有的Cookie值
     * @return array
     */
    public function getResponseCookies()
    {
        return $this->responseCookies;
    }

    /**
     * 服务器Response Header回调
     *
     * @param resource $ch Curl句柄
     * @param string $header 返回的请求头信息
     * @return int
     */
    public function headerCallback($ch, $header)
    {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/im', $header, $cookie)) {
            $this->responseCookies[$cookie[1]] = trim($cookie[2]);
        }

        $this->rawResponseHeaders .= $header;

        return strlen($header);
    }

    /**
     * 发起Get请求
     * @param string $url url地址
     * @param array $data get请求数据
     * @return null
     */
    public function get($url, $data = array())
    {
        if ('' == $url || is_null($url)) return null;

        $this->setURL($url, $data);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOption(CURLOPT_HTTPGET, true);

        return $this->exec();
    }

    /**
     * 发起Post请求
     * @param string $url url地址
     * @param array $data get请求数据
     * @return null
     */
    public function post($url, $data = array())
    {
        if ('' == $url || is_null($url)) return null;

        $this->setURL($url);
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, $this->buildPostData($data));

        return $this->exec();
    }

    public function buildPostData($data)
    {
        if (empty($data)) return '';

        $query = array();
        foreach ($data as $key => $value) {
            $query[] = urlencode($key) . '=' . rawurlencode($value);
        }

        return implode('&', $query);
    }

    /**
     * 关闭Curl请求资源
     */
    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}