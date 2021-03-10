<?php
namespace Kazumacchi\Request;

/**
 * GET, POSTアクセス
 */
class Request
{
    /**
     * ステータス200
     */
    const RESPONSE_STATUS_SUCCESS = 200;

    protected $http_header;

    /**
     * @param string $headers
     */
    public function __construct(array $headers = [])
    {
        // 基本的にjsonで返却する
        $this->http_header = array_merge(['Content-Type: application/json',], $headers);
    }

    /**
     * GETアクセス
     *
     * @param string $url
     *
     * @return
     */
    public function get(string $url)
    {
        return $this->request($url);
    }

    /**
     * POSTアクセス
     *
     * @param string $url
     * @param array  $params
     *
     * @return
     */
    public function post(string $url, array $params)
    {
        return $this->request($url, $params, true);
    }

    /**
     * リクエストをCURLで実行
     *
     * @param string $url
     * @param array  $forms
     * @param bool   $request_post
     *
     * @return string $response
     */
    private function request(string $url, array $params = [], bool $request_post = false): string
    {
        if (empty($url)) {
            throw new \Exception('NO CURL URL.');
        }

        // file_get_contentsは細かい調整が出来ないため、curlで実行
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_TIMEOUT,        30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 28);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->http_header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->http_header);
        }

        if ($request_post) {
            if (empty($params)) {
                throw new \Exception('Empty Post Request Param.');
            }

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $response = curl_exec($curl);

        // 正常終了しているかチェック
        $info = curl_getinfo($curl);
        if ($info['http_code'] !== self::RESPONSE_STATUS_SUCCESS) {
            throw new \Exception('curl Request Error: ' . curl_error($curl));
        }

        curl_close($curl);

        // json形式でない場合、NULLになる
        if (is_null(json_decode($response))) {
            throw new \Exception('Not Json Response.');
        }

        return $response;
    }
}