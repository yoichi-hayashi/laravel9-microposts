<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    /**
     * 投稿内容をお気に入りに追加するアクション
     */
    public function store($id)
    {
        // 認証済みユーザ（閲覧者）が、 投稿内容をお気に入りに追加する
        \Auth::user()->favorite($id);
        // 前のURLへリダイレクトさせる
        return back();
    }

    /**
     * 投稿内容をお気に入りから削除するアクション
     */
    public function destroy($id)
    {
        // 認証済みユーザ（閲覧者）が、 投稿内容をお気に入りから削除する
        \Auth::user()->unfavorite($id);
        // 前のURLへリダイレクトさせる
        return back();
    }
}
