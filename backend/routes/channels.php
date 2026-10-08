<?php

use App\Broadcasting\DocumentChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Document collaborative editing presence channel
Broadcast::channel('document.{documentId}', DocumentChannel::class);
