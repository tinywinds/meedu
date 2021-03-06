<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (! function_exists('flash')) {
    function flash($message, $level = 'warning')
    {
        $message = new \Illuminate\Support\MessageBag([$level => $message]);
        request()->session()->flash($level, $message);
    }
}

if (! function_exists('get_first_flash')) {
    /**
     * 获取第一条FLASH信息.
     *
     * @param $level
     *
     * @return mixed|string
     */
    function get_first_flash($level)
    {
        if (! session()->has($level)) {
            return '';
        }

        return session($level)->first();
    }
}

if (! function_exists('menu_is_active')) {
    /**
     * 指定路由名是否与当前访问的路由名相同.
     *
     * @param $routeName
     *
     * @return bool
     */
    function menu_is_active($routeName)
    {
        $routeName = strtolower($routeName);
        $currentRouteName = strtolower(request()->route()->getName());
        $isActive = $currentRouteName === $routeName ? 'active' : '';
        if (! $isActive && str_contains('.', $currentRouteName)) {
            $currentRouteNameArray = explode('.', $currentRouteName);
            unset($currentRouteNameArray[count($currentRouteNameArray) - 1]);
            $currentRouteName = implode('.', $currentRouteNameArray);
            $isActive = preg_match("/{$currentRouteName}[^_]/", $routeName) ? 'active' : '';
        }

        return $isActive;
    }
}

if (! function_exists('exception_response')) {
    /**
     * 异常响应.
     *
     * @param Exception $exception
     * @param string    $message
     *
     * @return array
     */
    function exception_response(Exception $exception, string $message = '')
    {
        return [
            'message' => $message ?: $exception->getMessage(),
            'code' => $exception->getCode() ?: 500,
        ];
    }
}

if (! function_exists('notification_name')) {
    /**
     * 获取Notification模板名.
     *
     * @param $notificationName
     *
     * @return string
     */
    function notification_name($notificationName)
    {
        $arr = explode('\\', $notificationName);
        $name = $arr[count($arr) - 1];

        return strtolower($name);
    }
}

if (! function_exists('at_user')) {
    /**
     * 艾特某个用户.
     *
     * @param $content
     * @param $fromUser
     * @param $from
     * @param $fromType
     */
    function at_user($content, $fromUser, $from, $fromType)
    {
        preg_match_all('/\s{1}@(.*?)\s{1}/', $content, $result);
        if (! ($result = optional($result)[1])) {
            return;
        }
        foreach ($result as $item) {
            event(new \App\Events\AtUserEvent($fromUser, $item, $from, $fromType));
        }
    }
}

if (! function_exists('at_notification_parse')) {
    /**
     * 艾特Notification内容输出.
     *
     * @param $notification
     *
     * @return string
     */
    function at_notification_parse($notification)
    {
        $data = $notification->data;
        $fromUser = \App\User::find($data['from_user_id']);
        $model = '\\App\\Models\\'.$data['from_type'];
        $from = (new $model())->whereId($data['from_id'])->first();
        $url = 'javascript:void(0)';
        switch ($data['from_type']) {
            case 'CourseComment':
                $url = route('course.show', [$from->course->id, $from->course->slug]);
                break;
            case 'VideoComment':
                $url = route('video.show', [$from->video->course->id, $from->video->id, $from->video->slug]);
                break;
        }

        return '<a href="'.$url.'">用户&nbsp;<b>'.$fromUser->nick_name.'</b>&nbsp;提到您啦。</a>';
    }
}

if (! function_exists('exception_record')) {
    /**
     * 记录异常.
     *
     * @param Exception $exception
     */
    function exception_record(Exception $exception): void
    {
        \Log::error([
            'file' => $exception->getFile(),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

if (! function_exists('admin')) {
    /**
     * 获取当前登录的管理员.
     *
     * @return \App\Models\Administrator
     */
    function admin()
    {
        return \Illuminate\Support\Facades\Auth::guard('administrator')->user();
    }
}

if (! function_exists('markdown_to_html')) {
    /**
     * markdown转换为html.
     *
     * @param $content
     *
     * @return string
     */
    function markdown_to_html($content)
    {
        return (new Parsedown())->parse($content);
    }
}

if (! function_exists('markdown_clean')) {
    /**
     * 过滤markdown非法字符串.
     *
     * @param string $markdownContent
     *
     * @return string
     */
    function markdown_clean(string $markdownContent)
    {
        $html = markdown_to_html($markdownContent);
        $safeHtml = clean($html);

        return (new \League\HTMLToMarkdown\HtmlConverter())->convert($safeHtml);
    }
}

if (! function_exists('image_url')) {
    /**
     * 给图片添加参数.
     *
     * @param $url
     *
     * @return string
     */
    function image_url($url)
    {
        $params = config('meedu.upload.image.params', '');

        return strstr('?', $url) !== false ? $url.$params : $url.'?'.$params;
    }
}

if (! function_exists('aliyun_play_auth')) {
    /**
     * 获取阿里云视频的播放Auth.
     *
     * @param \App\Models\Video $video
     *
     * @return mixed|SimpleXMLElement
     */
    function aliyun_play_auth(\App\Models\Video $video)
    {
        try {
            $profile = \DefaultProfile::getProfile(
                config('meedu.upload.video.aliyun.region', ''),
                config('meedu.upload.video.aliyun.access_key_id', ''),
                config('meedu.upload.video.aliyun.access_key_secret', '')
            );
            $client = new \DefaultAcsClient($profile);
            $request = new \vod\Request\V20170321\GetVideoPlayAuthRequest();
            $request->setAcceptFormat('JSON');
            $request->setRegionId(config('meedu.upload.video.aliyun.region', ''));
            $request->setVideoId($video->aliyun_video_id);
            $response = $client->getAcsResponse($request);

            return $response->PlayAuth;
        } catch (Exception $exception) {
            exception_record($exception);

            return '';
        }
    }
}
