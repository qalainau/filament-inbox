<?php

return [
    // Navigation
    'navigation_group' => '消息',
    'inbox' => '收件箱',
    'sent' => '已发送',
    'starred' => '已加星标',
    'trash' => '回收站',

    // Table columns
    'from' => '发件人',
    'to' => '收件人',
    'subject' => '主题',
    'received' => '接收时间',
    'sent_at' => '发送时间',
    'starred_at' => '加星时间',
    'deleted_at' => '删除时间',
    'status' => '状态',

    // Read status
    'unread' => '未读',
    'read' => '已读',
    'read_count' => '已读 :read/:total',

    // Filters
    'unread_only' => '仅未读',
    'starred_only' => '仅已加星标',

    // Actions
    'compose' => '写信',
    'compose_message' => '撰写消息',
    'reply' => '回复',
    'reply_all' => '回复全部',
    'forward' => '转发',
    'forward_message' => '转发消息',
    'star' => '加星标',
    'unstar' => '取消星标',
    'mark_as_read' => '标记为已读',
    'move_to_trash' => '移至回收站',
    'restore' => '恢复',
    'delete_permanently' => '永久删除',
    'empty_trash' => '清空回收站',
    'back_to_inbox' => '返回收件箱',
    'back_to_sent' => '返回已发送',
    'remove_from_sent' => '从已发送中删除',
    'delete' => '删除',

    // Compose form
    'recipient_to' => '收件人',

    // Notifications
    'message_sent' => '消息发送成功',
    'reply_sent' => '回复已发送',
    'message_forwarded' => '消息转发成功',
    'message_trashed' => '消息已移至回收站',
    'message_removed_from_sent' => '消息已从已发送中删除',
    'message_restored' => '消息已恢复',
    'message_deleted' => '消息已永久删除',
    'trash_emptied' => '回收站已清空',
    'new_message_from' => '来自 :name 的新消息',
    'replied' => ':name 回复了: :subject',

    // Forward
    'forwarded_message_header' => '---------- 转发的消息 ----------',
    'forwarded_from' => '发件人: :name',
    'forwarded_date' => '日期: :date',
    'forwarded_subject' => '主题: :subject',
    'forwarded_to' => '收件人: :names',

    // Confirmations
    'confirm_trash' => '确定要将此消息移至回收站吗？',
    'confirm_delete' => '确定要永久删除此消息吗？',
    'confirm_empty_trash' => '确定要永久删除回收站中的所有消息吗？',

    // Thread view
    'you' => '我',

    // Stats widget
    'unread_messages' => '未读消息',
    'total_messages' => '消息总数',
];
