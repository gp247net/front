```
  _____  _____     ___  _  _   _____ 
 / ____|  __ \   |__ \| || | |___  |
| |  __| |__) |     ) | || |_   / / 
| | |_ |  ___/     / /|__   _| / /  
| |__| | |        / /_   | |  / /   
 \_____|_|       |____|  |_| /_/    
```

> 🌐 **言語:** [🇻🇳 Tiếng Việt](./README_vi.md) · [🇬🇧 English](./README.md) · 🇯🇵 日本語（現在）

GP247 のフロントエンド基盤＆CMS パッケージ

`composer require gp247/front`

[![総ダウンロード数](https://poser.pugx.org/gp247/front/d/total.svg)](https://packagist.org/packages/gp247/front)
[![最新の安定版](https://poser.pugx.org/gp247/front/v/stable.svg)](https://packagist.org/packages/gp247/front)
[![ライセンス](https://poser.pugx.org/gp247/front/license.svg)](https://packagist.org/packages/gp247/front)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gp247net/front)

## 概要

GP247/Front は、GP247 の**フロントエンド層全体の基盤となるパッケージ**です — 単なる表示／テーマのレイヤーではありません。このパッケージは**公開ウェブサイト（ストアフロント）**と**コンテンツ管理システム（CMS）**を提供すると同時に、ほかのフロントエンドパッケージ（`gp247/shop` など）が継承する**共通インフラ**でもあります。完全な GP247 サイトには**必須**のコンポーネントで、管理画面層における `gp247/core` と同様の役割をフロントエンド層で担います。

主な機能：

- ページコンテンツの管理（Page / CMS）
- 柔軟なテンプレートシステム + 使用中のテンプレートに応じたビュー解決の仕組み
- 拡張可能なプラグインシステム
- ナビゲーション、メニュー、リンク、バナーの管理
- お問い合わせフォームと購読フォームの統合
- フロントエンドの共通インフラ（ベース Livewire コンポーネント、レイアウト、ルート）— ほかのパッケージが組み込めます

## インストール

1. パッケージをインストールします

    >`composer require gp247/front`


2. ルーティングを設定します：`bootstrap/app.php` ファイルを開きます：

  - web ルートの設定行をコメントアウトします：

    ```php
    //GP247 comment
    //web: __DIR__.'/../routes/web.php',
    ```


3. `bootstrap/providers.php` にサービスプロバイダーを登録します：
    ```php
    return [
        // ... 既存のプロバイダー
        GP247\Front\FrontServiceProvider::class,
    ];
    ```

4. インストールコマンドを実行します：
>`php artisan gp247:front-install`

## 主な機能

### ページ管理
- 静的ページの作成と管理
- ページごとの SEO 対応
- アクセス制御

### インターフェース
- 柔軟なテンプレートシステム
- セクションごとにカスタマイズできるレイアウト
- レスポンシブデザイン

管理画面のインターフェースをカスタマイズする：

>`php artisan vendor:publish --tag=gp247:front-admin`

ビューの保存先：`resources/views/vendor/gp247-front-admin`

デフォルトテンプレート（GP247Front）のビューを更新する：

>`php artisan vendor:publish --tag=gp247:front-view`

ビューの保存先：`app/GP247/Templates/GP247Front`

デフォルトテンプレートの公開リソース（css/js/画像）を publish する：

>`php artisan vendor:publish --tag=gp247:front-public`

リソースの保存先：`public/GP247/Templates/GP247Front`

### 拡張
- プラグインのサポート
- カスタムモジュールの統合
- 機能開発のための API

### コントローラーのオーバーライド（override）

>ステップ 1：オーバーライドしたいコントローラーファイルを `vendor/gp247/front/src/Controllers`（または `src/Api/Controllers`）-> `app/GP247/Front/Controllers`（または `app/GP247/Front/Api/Controllers`）にコピーします

>ステップ 2：名前空間を `GP247\Front\Controllers`（または `GP247\Front\Api\Controllers`）から `App\GP247\Front\Controllers`（または `App\GP247\Front\Api\Controllers`）に変更します — 先頭に `App` を追加するだけで、残りはそのままにします。

## ドキュメント
詳しいドキュメントは、[ドキュメント](https://gp247.net/en/docs) をご覧ください

## ライセンス
GP247/Front は、[MIT ライセンス](https://opensource.org/licenses/MIT) のもとで提供されるオープンソースソフトウェアです。
