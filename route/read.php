<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');

$tid = param(1, 0);
empty($tid) AND message(-1, lang('data_malformation'));

$page = param(2, 1);
$pagesize = $conf['comment_pagesize'];
$extra = array(); // 插件预留

// hook read_start.php

$thread = well_thread_read_cache($tid);
empty($thread) AND message(-1, lang('thread_not_exists'));

// hook read_before.php

$fid = $thread['fid'];
$forum = isset($forumlist[$fid]) ? $forumlist[$fid] : NULL;
empty($forum) AND message(-1, lang('forum_not_exists'));

// hook read_center.php

// 用户读取版块主题的权限
forum_access_user($fid, $gid, 'allowread') || message(-1, lang('user_group_insufficient_privilege'));

// hook read_middle.php

// 大站可用单独的点击服务，减少 db 压力 / if request is huge, separate it from mysql server
well_thread_inc_views($tid);

// hook read_after.php

if ($thread['type'] == 0) {
    // 文章 / Article

    // hook read_article_start.php

    $data = NULL;
    $arrlist = NULL;
    // 从默认的地方读取主题数据
    $thread_read_from_default = 1;

    // hook read_article_default_start.php

    if ($thread_read_from_default == 1) {

        // hook read_article_default_before.php

        $postlist = ($forum['comment'] AND $thread['closed'] < 2 AND $thread['posts'] > 0) ? comment_find_by_tid($tid, $page, $pagesize) : NULL;

        // hook read_article_default_center.php

        if ($page == 1) {

            $attachlist = array();
            $imagelist = array();
            $thread['filelist'] = array();

            // hook read_article_default_page_before.php

            $thread['files'] > 0 AND list($attachlist, $imagelist, $thread['filelist']) = well_attach_find_by_tid($tid);

            // hook read_article_default_page_center.php

            $data = data_read_cache($tid);
            empty($data) AND message(-1, lang('data_malformation'));

            // hook read_article_default_page_after.php
        }

        // hook read_article_default_middle.php

        $allowpost = forum_access_user($fid, $gid, 'allowpost') ? 1 : 0;
        $allowupdate = forum_access_mod($fid, $gid, 'allowupdate') ? 1 : 0;
        $allowdelete = forum_access_mod($fid, $gid, 'allowdelete') ? 1 : 0;

        // hook read_article_default_after.php
    }

    // hook read_article_default_end.php

    // 默认拉取其他主题
    $pull_other_from_default = 1;

    // hook read_article_center.php

    if ($pull_other_from_default == 1) {
        // hook read_article_pull_other_start.php

        // 相关主题等调用，统一遍历tid合并去重，再遍历主题表
        $arrlist = thread_other_pull($thread);

        // hook read_article_pull_other_center.php

        // 主题所在版块下所有展示属性主题
        $flaglist = array_value($arrlist, 'flaglist');

        // hook read_article_pull_other_end.php
    }

    // hook read_article_middle.php

    $pagination = pagination(url('read-' . $tid . '-{page}', $extra), $thread['posts'], $page, $pagesize);

    // hook read_article_after.php

    $header['title'] = $thread['subject'] . '-' . $forum['name'] . '-' . $conf['sitename'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('read-' . $tid, $extra);
    $header['keywords'] = $thread['keyword'] ? $thread['keyword'] : $thread['subject'];
    $header['description'] = $thread['description'] ? $thread['description'] : $thread['brief'];
    $_SESSION['fid'] = $fid;

    // hook read_article_end.php

    if ($ajax) {
        empty($conf['api_on']) AND message(0, lang('closed'));
        well_thread_filter($thread);
        message(0, array('thread' => $thread, 'thread_data' => $data, 'arrlist' => $arrlist));
    } else {
        include _include(theme_load(3, $fid));
    }

} elseif ($thread['type'] == 10) {
    // 主题外链 / thread external link
    // hook read_link_before.php
    http_location(trim($thread['description']));
} elseif ($thread['type'] == 11) {
    // 单页 / single page
    // hook read_single_page_start.php

    $attachlist = array();
    $imagelist = array();
    $thread['filelist'] = array();

    // hook read_single_page_before.php

    $thread['files'] > 0 AND list($attachlist, $imagelist, $thread['filelist']) = well_attach_find_by_tid($tid);

    // hook read_single_page_center.php

    $data = data_read_cache($tid);
    empty($data) AND message(-1, lang('data_malformation'));

    // hook read_single_page_middle.php

    $allowpost = forum_access_user($fid, $gid, 'allowpost') ? 1 : 0;
    $allowupdate = forum_access_mod($fid, $gid, 'allowupdate') ? 1 : 0;
    $allowdelete = forum_access_mod($fid, $gid, 'allowdelete') ? 1 : 0;

    $tidlist = $forum['threads'] ? page_find_by_fid($fid, $page, $pagesize) : NULL;

    // hook read_single_page_threadlist_before.php

    if ($tidlist) {
        $tidarr = arrlist_values($tidlist, 'tid');
        // hook read_single_page_threadlist_center.php
        $threadlist = well_thread_find($tidarr, $pagesize);
        // 按之前tidlist排序
        $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
        // hook read_single_page_threadlist_after.php
    }

    // hook read_single_page_after.php

    $header['title'] = $thread['subject'] . '-' . $forum['name'] . '-' . $conf['sitename'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('read-' . $tid, $extra);
    $header['keywords'] = $thread['keyword'] ? $thread['keyword'] : $thread['subject'];
    $header['description'] = $thread['description'] ? $thread['description'] : $thread['brief'];
    $_SESSION['fid'] = $fid;

    // hook read_single_page_end.php

    if ($ajax) {
        empty($conf['api_on']) AND message(0, lang('closed'));
        well_thread_filter($thread);
        message(0, array('thread' => $thread, 'data' => $data));
    } else {
        include _include(theme_load(15, $fid));
    }
}

// hook read_end.php

?>