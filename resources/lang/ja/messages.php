<?php

return [
    // Navigation
    'navigation_group' => 'メッセージ',
    'inbox' => '受信箱',
    'sent' => '送信済み',
    'starred' => 'スター付き',
    'trash' => 'ゴミ箱',

    // Table columns
    'from' => '差出人',
    'to' => '宛先',
    'subject' => '件名',
    'received' => '受信日時',
    'sent_at' => '送信日時',
    'starred_at' => 'スター日時',
    'deleted_at' => '削除日時',
    'status' => 'ステータス',

    // Read status
    'unread' => '未読',
    'read' => '既読',
    'read_count' => '既読 :read/:total',

    // Filters
    'unread_only' => '未読のみ',
    'starred_only' => 'スター付きのみ',

    // Actions
    'compose' => '新規作成',
    'compose_message' => 'メッセージを作成',
    'reply' => '返信',
    'reply_all' => '全員に返信',
    'forward' => '転送',
    'forward_message' => 'メッセージを転送',
    'star' => 'スターを付ける',
    'unstar' => 'スターを外す',
    'mark_as_read' => '既読にする',
    'move_to_trash' => 'ゴミ箱に移動',
    'restore' => '復元',
    'delete_permanently' => '完全に削除',
    'empty_trash' => 'ゴミ箱を空にする',
    'back_to_inbox' => '受信箱に戻る',
    'back_to_sent' => '送信済みに戻る',
    'remove_from_sent' => '送信済みから削除',
    'delete' => '削除',

    // Compose form
    'recipient_to' => '宛先',

    // Notifications
    'message_sent' => 'メッセージを送信しました',
    'reply_sent' => '返信を送信しました',
    'message_forwarded' => 'メッセージを転送しました',
    'message_trashed' => 'メッセージをゴミ箱に移動しました',
    'message_removed_from_sent' => '送信済みから削除しました',
    'message_restored' => 'メッセージを復元しました',
    'message_deleted' => 'メッセージを完全に削除しました',
    'trash_emptied' => 'ゴミ箱を空にしました',
    'new_message_from' => ':name からの新しいメッセージ',
    'replied' => ':name が返信しました: :subject',

    // Forward
    'forwarded_message_header' => '---------- 転送されたメッセージ ----------',
    'forwarded_from' => '差出人: :name',
    'forwarded_date' => '日時: :date',
    'forwarded_subject' => '件名: :subject',
    'forwarded_to' => '宛先: :names',

    // Confirmations
    'confirm_trash' => 'このメッセージをゴミ箱に移動しますか？',
    'confirm_delete' => 'このメッセージを完全に削除しますか？',
    'confirm_empty_trash' => 'ゴミ箱のメッセージをすべて完全に削除しますか？',

    // Thread view
    'you' => '自分',

    // Stats widget
    'unread_messages' => '未読メッセージ',
    'total_messages' => 'メッセージ総数',
];
