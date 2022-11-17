<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * このユーザが所有する投稿。（ Micropostモデルとの関係を定義）
     */
    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }
    
    /**
     * このユーザに関係するモデルの件数をロードする。
     */
    public function loadRelationshipCounts()
    {
        $this->loadCount(['microposts', 'followings', 'followers', 'favorites']);
    }
    
    /**
     * このユーザがフォロー中のユーザ。（Userモデルとの関係を定義）
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }
    
    /**
     * このユーザをフォロー中のユーザ。（Userモデルとの関係を定義）
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }
    
    /**
     * このユーザが追加したお気に入り一覧 
     */
     public function favorites()
     {
        return $this->belongsToMany(Micropost::class, 'favorites', 'user_id', 'micropost_id');
     }
    
    /**
     * $userIdで指定されたユーザをフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function follow($userId)
    {
        $exist = $this->is_following($userId);
        $its_me = $this->id == $userId;
        
        if ($exist || $its_me) {
            return false;
        } else {
            $this->followings()->attach($userId);
            return true;
        }
    }
    
    /**
     * $userIdで指定されたユーザをアンフォローする。
     * 
     * @param  int $usereId
     * @return bool
     */
    public function unfollow($userId)
    {
        $exist = $this->is_following($userId);
        $its_me = $this->id == $userId;
        
        if ($exist && !$its_me) {
            $this->followings()->detach($userId);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 指定された$userIdのユーザをこのユーザがフォロー中であるか調べる。フォロー中ならtrueを返す。
     * 
     * @param  int $userId
     * @return bool
     */
    public function is_following($userId)
    {
        return $this->followings()->where('follow_id', $userId)->exists();
    }
    
    /**
     * このユーザとフォロー中ユーザの投稿に絞り込む。
     */
    public function feed_microposts()
    {
        // このユーザがフォロー中のユーザのidを取得して配列にする
        $userIds = $this->followings()->pluck('users.id')->toArray();
        // このユーザのidもその配列に追加
        $userIds[] = $this->id;
        // それらのユーザが所有する投稿に絞り込む
        return Micropost::whereIn('user_id', $userIds);
    }
    
    /**
     * $micropost_idで指定された投稿内容をお気に入りに追加する。
     */
    public function favorite($micropost_id)
    {
        // すでにお気に入りに追加しているか
        $exist = $this->is_favorite($micropost_id);

        if ($exist) {
            // お気に入りに追加済みの場合は何もしない
            return false;
        } else {
            // 上記以外はお気に入りに追加する
            $this->favorites()->attach($micropost_id);
            return true;
        }
    }

    /**
     * $micropost_idで指定された投稿内容をお気に入りから削除する。
     */
    public function unfavorite($micropost_id)
    {
        // すでにお気に入りに追加しているか
        $exist = $this->is_favorite($micropost_id);

        if ($exist) {
            // お気に入りに追加済みの場合はお気に入りから削除する
            $this->favorites()->detach($micropost_id);
            return true;
        } else {
            // 上記以外の場合は何もしない
            return false;
        }
    }

    /**
     * 指定された $micropost_idの投稿内容をこのユーザがお気に入りに追加しているか調べる。お気に入りに追加しているならtrueを返す。
     */
    public function is_favorite($micropost_id)
    {
        // お気に入りに追加している投稿内容の中に $micropost_idのものが存在するか
        return $this->favorites()->where('micropost_id', $micropost_id)->exists();
    }
}
