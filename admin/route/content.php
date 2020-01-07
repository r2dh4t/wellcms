<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

group_access($gid, 'managecontent') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook admin_content_start.php

// 返回CMS栏目数据(仅列表)
$columnlist = category_list($forumlist);

// hook admin_content_before.php

if ($action == 'list') {
    // content-list-fid-page

    // hook admin_content_list_start.php

    if ($method == 'GET') {

        // hook admin_content_list_get_start.php

        $fid = param(2, 0);
        $page = param(3, 1);
        $pagesize = 20;
        $orderby = param(4, 0); // 主题排序

        // hook admin_content_list_get_before.php

        // 插件预留
        $extra = array('fid' => $fid, 'backstage' => 1);

        // hook admin_content_list_get_center.php

        /* 所有通过审核的内容，免费版无审核功能
         * 遍历所有tid，然后合并tid再查询thread表，避免重复查询
         * */
        if ($fid) { // 版块下的主题

            // hook admin_content_list_get_forum_before.php

            $forum = array_value($forumlist, $fid);
            empty($forum) AND message(1, lang('forum_not_exists'));

            // hook admin_content_list_get_forum_after.php

            $n = $forum['threads'];

            // hook admin_content_list_get_forum_thread_before.php

            // 栏目下主题
            if ($orderby == 0) {
                // 返回栏目下tid
                $tidlist = $n ? well_thread_find_tid($fid, $page, $pagesize) : NULL;
            }
            /* else {
                // 主题排序
                $tidlist = $n ? well_thread_find_desc($fid, $page, $pagesize) : NULL;
            }*/

            // hook admin_content_list_get_forum_thread_after.php

        } else {
            // 主页读取全部主题

            // hook admin_content_list_get_count_before.php

            $n = thread_tid_count();

            // hook admin_content_list_get_count_after.php

            $tidlist = $n ? thread_tid_find($page, $pagesize) : NULL;

            // hook admin_content_list_get_page_after.php
        }

        // hook admin_content_list_get_middle.php

        // 查找置顶 1栏目 2频道 3全局
        if ($page == 1) {
            $stickylist = $fid ? sticky_list_thread($fid) : sticky_index_thread();
            $tidlist = (array)$stickylist + (array)$tidlist;
        }

        // hook admin_content_list_get_sticky_after.php

        if (empty($tidlist)) {
            $threadlist = NULL;
        } else {
            $tidarr = arrlist_values($tidlist, 'tid');
            $threadlist = well_thread_find($tidarr, count($tidlist));
            // 按之前tidlist排序
            $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
        }

        $pagination = pagination(url('content-list-' . $fid . '-{page}', $extra), $n, $page, $pagesize);

        // hook admin_content_list_get_after.php

        $header['title'] = lang('content');
        $header['mobile_title'] = lang('content');

        // hook admin_content_list_get_end.php

        include _include(ADMIN_PATH . 'view/htm/content_list.htm');

    } elseif ($method == 'POST') {

        // hook admin_content_list_post_start.php

        // 主题排序
        /*$arr = _POST('data');

        empty($arr) && message(1, lang('update_failed'));

        foreach ($arr as &$val) {
            $rank = intval($val['rank']);
            $tid = intval($val['tid']);
            intval($val['oldrank']) != $rank && $tid && $r = thread_tid_update_rank($tid, $rank);

        }

        message(0, lang('update_successfully'));*/

        // hook admin_content_list_post_end.php
    }

} elseif ($action == 'create') {

    // hook admin_content_create_start.php

    if ($method == 'GET') {

        // hook admin_content_create_get_start.php

        $fid = param(2, 0);
        $forum = $fid ? array_value($forumlist, $fid) : array();
        $model = array_value($forum, 'model', 0);

        // hook admin_content_create_get_before.php

        $forum_flagids = array();
        $category_flagids = array();
        $index_flagids = array();

        $index_flag = flag_forum_show(0);
        $index_flag AND flag_filter($index_flag);

        // hook admin_content_create_get_middle.php

        // 过滤权限
        $forumlist_allowthread = forum_list_access_filter($forumlist, $gid, 'allowthread');

        empty($forumlist_allowthread) AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_content_create_get_filter_after.php

        // 获取主图
        $thumbnail = admin_view_path() . 'img/nopic.png';

        // hook admin_content_create_get_thumbnail_after.php

        $picture = $config['picture_size'];
        $pic_width = $picture['width'];
        $pic_height = $picture['height'];

        // hook admin_content_create_get_form_before.php

        $input = $filelist = array();
        $form_title = lang('increase') . lang('content');
        $form_action = url('content-create-' . $fid);
        $form_submit_txt = lang('submit');
        $form_subject = $form_message = $form_brief = $form_link = $form_closed = $form_keyword = $form_description = $tagstr = '';

        $setting = array_value($config, 'setting');
        $thumbnail_on = array_value($setting, 'thumbnail_on', 0) == 1 ? 'checked="checked"' : '';
        $save_image = array_value($setting, 'save_image_on', 0) == 1 ? 'checked="checked"' : '';
        $form_doctype = 0;
        $_fid = 0;
        $page = 0;
        $_SESSION['tmp_website_files'] = $_SESSION['tmp_thumbnail'] = array();

        // hook admin_content_create_get_form_after.php

        $breadcrumb_flag = lang('increase') . lang('content');

        // hook admin_content_create_get_after.php

        $header['title'] = lang('increase') . lang('content');
        $header['mobile_title'] = lang('increase') . lang('content');

        // 过滤版块相关数据
        $forumlist = forum_filter($forumlist);

        // hook admin_content_create_get_template.php

        // 可以根据自己设计的添加内容界面绑定栏目，绑定模型，显示不同的界面
        /*if ($model == 0) {
            // 加载
            include _include(ADMIN_PATH . 'view/htm/content_post.htm');
            exit;
        }*/

        include _include(ADMIN_PATH . 'view/htm/content_post.htm');

        // hook admin_content_create_get_end.php

    } elseif ($method == 'POST') {

        group_access($gid, 'managecreatethread') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_content_create_post_start.php

        $fid = param('fid', 0);
        $forum = array_value($forumlist, $fid);
        empty($forum) AND message('fid', lang('forum_not_exists'));

        // hook admin_content_create_post_forum_after.php

        // 普通用户权限判断
        $r = forum_access_user($fid, $gid, 'allowthread');
        empty($r) AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_content_create_post_access_after.php

        $subject = param('subject', '', FALSE);

        empty($subject) ? message('subject', lang('please_input_subject')) : $subject = xn_html_safe(filter_all_html($subject));

        xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
        // 过滤标题 关键词

        // hook admin_content_create_post_subject_after.php

        $link = param('link', 0);
        $type = $link ? 10 : 0;
        // hook admin_content_create_post_link_after.php

        $closed = param('closed', 0);
        $thumbnail = param('thumbnail', 0);
        $delete_pic = param('delete_pic', 0);
        $save_image = param('save_image', 0);
        $brief_auto = param('brief_auto', 0);
        $doctype = param('doctype', 0);
        $doctype > 10 AND message(1, lang('doc_type_not_supported'));

        // hook admin_content_create_post_before.php

        $message = $_message = '';
        if ($link == 0) {
            $message = param('message', '', FALSE);
            empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

            // 过滤所有html标签
            $_message = filter_all_html($message);

            // 过滤内容 关键词

            // hook admin_content_create_post_message_after.php
        }

        // hook admin_content_create_post_brief_start.php

        $brief = param('brief');
        if ($brief) {

            $brief = xn_html_safe(filter_all_html($brief));

            // 过滤简介 关键词
            // hook admin_content_create_post_brief_before.php

            xn_strlen($brief) > 120 AND $brief = xn_substr($brief, 0, 120);
        } else {
            $brief = ($brief_auto AND $_message) ? xn_substr($_message, 0, 120) : '';
        }

        // hook admin_content_create_post_brief_end.php

        $keyword = param('keyword');
        $keyword = xn_html_safe(filter_all_html($keyword));
        // 过滤内容 关键词
        // hook admin_content_create_post_keyword_before.php
        // 超出则截取
        xn_strlen($keyword) > 64 AND $keyword = xn_substr($keyword, 0, 64);

        // hook admin_content_create_post_description_before.php

        $description = param('description');
        $description = xn_html_safe(filter_all_html($description));
        // 过滤内容 关键词
        // hook admin_content_create_post_description_center.php
        // 超出则截取
        xn_strlen($description) > 120 AND $description = xn_substr($description, 0, 120);

        // hook admin_content_create_post_description_after.php

        $tags = param('tags', '', FALSE);
        $tags = xn_html_safe(filter_all_html(trim($tags, ',')));
        // 过滤标签 关键词
        // hook admin_content_create_post_tag_center.php

        // hook admin_content_create_post_tag_after.php

        // 首页flag
        $flag_index_arr = array_filter(param('index', array()));
        // 频道flag
        $flag_cate_arr = array_filter(param('category', array()));
        // 栏目flag
        $flag_forum_arr = array_filter(param('forum', array()));
        // 统计主题绑定flag数量
        $flags = count($flag_index_arr) + count($flag_cate_arr) + count($flag_forum_arr);

        // hook admin_content_create_post_flags.php

        $thread = array(
            'fid' => $fid,
            'type' => $type,
            'doctype' => $doctype,
            'subject' => $subject,
            'brief' => $brief,
            'keyword' => $keyword,
            'description' => $description,
            'closed' => $closed,
            'flags' => $flags,
            'thumbnail' => $thumbnail,
            'save_image' => $save_image,
            'delete_pic' => $delete_pic,
            'message' => $message
        );

        // hook admin_content_create_post_middle.php

        $tid = well_thread_create($thread);
        $tid === FALSE AND message(-1, lang('create_thread_failed'));
        unset($thread);

        // hook admin_content_create_post_after.php

        $tag_json = well_tag_post($tid, $fid, $tags);
        well_thread_update($tid, array('tag' => $tag_json)) === FALSE AND message(-1, lang('update_thread_failed'));

        // 首页flag
        !empty($flag_index_arr) AND flag_create_thread(0, 1, $tid, $flag_index_arr) === FALSE AND message(-1, lang('create_failed'));

        // 频道flag
        $forum['fup'] AND !empty($flag_cate_arr) AND flag_create_thread($forum['fup'], 2, $tid, $flag_cate_arr) === FALSE AND message(-1, lang('create_failed'));

        // 栏目flag
        !empty($flag_forum_arr) AND flag_create_thread($fid, 3, $tid, $flag_forum_arr) === FALSE AND message(-1, lang('create_failed'));

        // hook admin_content_create_post_end.php

        message(0, lang('create_successfully'));
    }

} elseif ($action == 'update') {

    group_access($gid, 'managecreatethread') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

    // hook admin_content_update_start.php

    $tid = param(2, 0);
    empty($tid) AND message(1, lang('data_malformation'));

    $_fid = param('fid', 0);
    $page = param(3, 0);

    $thread = well_thread_read_cache($tid);
    empty($thread) AND message(-1, lang('thread_not_exists'));
    $fid = $thread['fid'];

    // hook admin_content_update_before.php

    $thread_data = data_read($tid);

    // hook admin_content_update_after.php

    // 主题绑定了哪些flag array(1,2,3)
    list($index_flagids, $category_flagids, $forum_flagids, $flagarr) = flag_forum_by_tid($tid);

    // hook admin_content_update_end.php

    if ($method == 'GET') {

        // hook admin_content_update_get_start.php

        $forum = array_value($forumlist, $fid);
        $model = array_value($forum, 'model', 0);

        // hook admin_content_update_get_forum_after.php

        $index_flag = flag_forum_show(0);
        $index_flag AND flag_filter($index_flag);

        // hook admin_content_update_get_flag_after.php

        // 获取主图
        $thread['icon_text'] = $thread['icon'] ? $thread['icon_text'] : url_path() . $thread['icon_text'];

        // hook admin_content_update_get_icon_after.php

        $picture = $config['picture_size'];
        $pic_width = $picture['width'];
        $pic_height = $picture['height'];

        // hook admin_content_update_get_files_before.php

        $attachlist = array();
        $imagelist = array();
        $input = array();
        $filelist = array();
        $thread['files'] AND list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid);

        $tagstr = $thread['tag_text'] ? implode(',', $thread['tag_text']) . ',' : '';

        // hook admin_content_update_get_files_after.php

        $form_title = lang('edit');
        $form_action = url('content-update-' . $tid . '-' . $page);
        $form_submit_txt = lang('submit');
        $form_subject = $thread['subject'];
        $form_message = strpos($thread_data['message'], '="upload/') !== FALSE ? str_replace('="upload/', '="../upload/', $thread_data['message']) : $thread_data['message'];
        $form_brief = $thread['brief'];
        $form_doctype = $thread_data['doctype'];
        $form_link = $thread['type'] == 10 ? 'checked="checked"' : '';
        $form_closed = $thread['closed'] >= 1 ? 'checked="checked"' : '';
        $form_keyword = $thread['keyword'];
        $form_description = $thread['description'];
        empty($filelist) || $filelist += (array)_SESSION('tmp_website_files');
        $thumbnail = $thread['icon_text'];

        $setting = array_value($config, 'setting');
        $save_image = array_value($setting, 'save_image_on', 0) == 1 ? 'checked="checked"' : '';
        // hook admin_content_update_get_form_after.php

        $breadcrumb_flag = lang('edit');

        // hook admin_content_update_get_after.php

        $header['title'] = lang('edit');
        $header['mobile_title'] = lang('edit');

        // 过滤版块相关数据
        $forumlist = forum_filter($forumlist);

        // hook admin_content_update_get_template.php

        // 可以根据自己设计的添加内容界面绑定栏目，绑定模型，显示不同的界面
        /*if ($model == 0) {
            // 加载
            include _include(ADMIN_PATH . 'view/htm/content_post.htm');
            exit;
        }*/

        include _include(ADMIN_PATH . 'view/htm/content_post.htm');

        // hook admin_content_update_get_end.php

    } elseif ($method == 'POST') {

        // hook admin_content_update_post_start.php

        $arr = array();

        $subject = param('subject', '', FALSE);
        empty($subject) ? message('subject', lang('please_input_subject')) : $subject = xn_html_safe(filter_all_html($subject));

        xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
        // 过滤标题 关键词

        // hook admin_content_update_post_subject_before.php

        if ($subject != $thread['subject']) {
            (mb_strlen($subject, 'UTF-8') > 80) ? message('subject', lang('subject_max_length', array('max' => 80))) : $arr['subject'] = $subject;

            $thread['sticky'] > 0 AND cache_delete('sticky_thread_list');
        }

        // hook admin_content_update_post_subject_after.php

        $link = param('link', 0);
        if ($link && $thread['type'] != 10) {
            $arr['type'] = 10;
        } elseif (empty($link) && $thread['type'] == 10) {
            $arr['type'] = 0;
        }

        // hook admin_content_update_post_link_after.php

        $closed = param('closed', 0);
        $closed != $thread['closed'] AND $arr['closed'] = $closed;

        // hook admin_content_update_post_closed_after.php

        $doctype = param('doctype', 0);
        $doctype > 10 AND message(1, lang('doc_type_not_supported'));

        // hook admin_content_update_post_message_before.php

        $message = $_message = '';
        if ($link == 0) {
            $message = param('message', '', FALSE);
            empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

            $_message = filter_all_html($message);
            // 过滤内容 关键词

            // hook admin_content_update_post_message_center.php
        }

        // hook admin_content_update_post_message_after.php

        $brief_auto = param('brief_auto', 0);
        $brief = param('brief');
        if ($brief) {

            $brief = xn_html_safe(filter_all_html($brief));

            // 过滤简介 关键词
            // hook admin_content_update_post_brief_before.php

            xn_strlen($brief) > 120 AND $brief = xn_substr($brief, 0, 120);
        } else {
            $brief = ($brief_auto AND $_message) ? xn_html_safe(xn_substr($_message, 0, 120)) : '';
        }

        // hook admin_content_update_post_brief_after.php

        $brief != $thread['brief'] AND $arr['brief'] = $brief;

        // hook admin_content_update_post_keyword_before.php

        $keyword = param('keyword');
        $keyword = xn_html_safe(filter_all_html($keyword));
        // 过滤内容 关键词
        // hook admin_content_update_post_keyword_center.php
        // 超出则截取
        xn_strlen($keyword) > 64 AND $keyword = xn_substr($keyword, 0, 64);

        $keyword != $thread['keyword'] AND $arr['keyword'] = $keyword;

        // hook admin_content_update_post_keyword_after.php

        $description = param('description');
        $description = xn_html_safe(filter_all_html($description));
        // 过滤内容 关键词
        // hook admin_content_update_post_description_before.php
        // 超出则截取
        xn_strlen($description) > 120 AND $description = xn_substr($description, 0, 120);
        $description != $thread['description'] AND $arr['description'] = $description;

        // hook admin_content_update_post_fid_before.php

        $newfid = param('fid', 0);
        $forum = array_value($forumlist, $fid);
        empty($forum) AND message('fid', lang('forum_not_exists'));

        // hook admin_content_update_post_fid_center.php

        if ($fid != $newfid) {

            // hook admin_content_update_post_fid_access.php

            $thread['uid'] != $uid AND !forum_access_mod($fid, $gid, 'allowupdate') AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_content_update_post_fid_update.php

            forum__update($newfid, array('threads+' => 1));
            forum_update($thread['fid'], array('threads-' => 1));
            sticky_thread_update_by_tid($tid, $newfid);

            thread_tid_update($tid, $newfid);

            $arr['fid'] = $newfid;
        }

        // 1 删除主图
        $delete_pic = param('delete_pic', 0);
        // hook admin_content_update_post_fid_after.php
        $upload_thumbnail = well_attach_assoc_type(0);
        if (!empty($upload_thumbnail) || $delete_pic) {
            // Ym变更删除旧图
            $attach_dir_save_rule = array_value($conf, 'well_attach_dir_save_rule', 'Ym');
            $old_day = $thread['icon'] ? date($attach_dir_save_rule, $thread['icon']) : '';
            $day = date($attach_dir_save_rule, $time);
            if ($day != $old_day || $delete_pic) {
                $file = $conf['upload_path'] . 'thumbnail/' . $old_day . '/' . $thread['uid'] . '_' . $tid . '_' . $thread['icon'] . '.jpeg';
                file_exists($file) AND unlink($file);
            }

            // hook admin_content_update_post_unlink_after.php

            if ($delete_pic) {
                $arr['icon'] = 0;
            } else {
                $arr['icon'] = $time;
                // 关联主图 type 0或空内容图片或附件 1:内容主图 8:节点主图 9:节点tag主图 教练套课主图
                $thumbnail = array('tid' => $tid, 'uid' => $uid, 'type' => 0);
                // hook admin_content_update_post_attach_before.php
                well_attach_assoc_post($thumbnail);
                unset($thumbnail);
            }
        }

        // hook admin_content_update_post_attach_after.php

        $tags = param('tags', '', FALSE);
        $tags = xn_html_safe(filter_all_html(trim($tags, ',')));
        // 过滤标签 关键词
        // hook admin_content_update_post_tag_center.php

        $tag_json = well_tag_post_update($tid, $fid, $tags, $thread['tag_text']);

        $arr['tag'] = $tag_json != $thread['tag_text'] ? $tag_json : $thread['tag_text'];

        // hook admin_content_update_post_tag_after.php

        // 首页flag
        $flag_index_arr = array_filter(param('index', array()));
        // 首页需要再创建的
        $new_index_flagids = empty($flag_index_arr) ? array() : array_diff($flag_index_arr, $index_flagids);
        // 返回首页被取消的flagid
        $old_index_flagids = array_diff($index_flagids, $flag_index_arr);

        // 频道flag
        $flag_cate_arr = array_filter(param('category', array()));
        // 频道需要再创建的
        $new_cate_flagids = empty($flag_cate_arr) ? array() : array_diff($flag_cate_arr, $category_flagids);
        // 返回频道被取消的flagid
        $old_cate_flagids = array_diff($category_flagids, $flag_cate_arr);

        // 栏目flag
        $flag_forum_arr = array_filter(param('forum', array()));
        // 需要再创建的
        $new_forum_flagids = empty($flag_forum_arr) ? array() : array_diff($flag_forum_arr, $forum_flagids);
        // 返回被取消的flagid
        $old_forum_flagids = array_diff($forum_flagids, $flag_forum_arr);

        $flags = $thread['flags'] + count($new_index_flagids) + count($new_cate_flagids) + count($new_forum_flagids) - count($old_index_flagids) - count($old_cate_flagids) - count($old_forum_flagids);
        $thread['flags'] != $flags AND $arr['flags'] = $flags;

        // hook admin_content_update_post_arr_after.php

        !empty($arr) AND well_thread_update($tid, $arr) === FALSE AND message(-1, lang('update_thread_failed'));
        unset($arr);

        // hook admin_content_update_post_before.php

        // $link = 1 为站外链接 无需更新数据表
        if ($link == 0) {

            $save_image = param('save_image', 0);
            $save_image AND $message = well_save_remote_image(array('tid' => $tid, 'fid' => $fid, 'uid' => $uid, 'message' => $message));

            // 关联附件
            $attach = array('tid' => $tid, 'uid' => $uid, 'type' => 1, 'images' => $thread['images'], 'files' => $thread['files'], 'message' => $message);
            $message = well_attach_assoc_post($attach);
            unset($attach);

            // 如果开启云储存或使用图床，需要把内容中的附件链接替换掉
            $message = data_message_replace_url($tid, $message);

            $update = array('tid' => $tid, 'gid' => $gid, 'doctype' => $doctype, 'message' => $message);
            data_update($tid, $update) === FALSE AND message(-1, lang('update_post_failed'));
            unset($update);
        }

        // hook admin_content_update_post_center.php

        // 首页flag
        !empty($new_index_flagids) AND flag_create_thread(0, 1, $tid, $new_index_flagids) === FALSE AND message(-1, lang('create_failed'));

        // 返回首页被取消的flagid
        !empty($old_index_flagids) AND flag_thread_delete_by_ids($old_index_flagids, $flagarr);

        // 频道flag
        $forum['fup'] AND !empty($new_cate_flagids) AND flag_create_thread($forum['fup'], 2, $tid, $new_cate_flagids) === FALSE AND message(-1, lang('create_failed'));
        // 返回频道被取消的flagid
        !empty($old_cate_flagids) AND flag_thread_delete_by_ids($old_cate_flagids, $flagarr);

        // 栏目flag
        !empty($new_forum_flagids) AND flag_create_thread($fid, 3, $tid, $new_forum_flagids) === FALSE AND message(-1, lang('create_failed'));
        // 返回被取消的flagid
        !empty($old_forum_flagids) AND flag_thread_delete_by_ids($old_forum_flagids, $flagarr);

        // hook admin_content_update_post_end.php

        message(0, lang('update_successfully'));
    }

}

// hook admin_content_end.php

?>