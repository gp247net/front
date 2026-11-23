```
  _____  _____     ___  _  _   _____ 
 / ____|  __ \   |__ \| || | |___  |
| |  __| |__) |     ) | || |_   / / 
| | |_ |  ___/     / /|__   _| / /  
| |__| | |        / /_   | |  / /   
 \_____|_|       |____|  |_| /_/    
```

> 🌐 **言語:** [🇻🇳 Tiếng Việt](./README_vi.md) · [🇬🇧 English](./README.md) · 🇯🇵 日本語（現在）

[![Tests](https://github.com/gp247net/front/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/gp247net/front/actions/workflows/tests.yml)

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

> 📘 **インストールの詳しいガイド** — インストール構成の選び方（S-Cart、GP247 CMS、core のみ）、コマンドラインのないホスティング、既存サイトへの front/shop 追加、トラブルシューティング：[GP247 と S-Cart のインストール](https://github.com/gp247net/gp247-docs/blob/main/install-upgrade/install_ja.md)。

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

## テストの実行

`gp247/front` のテストはこのリポジトリの `tests/` フォルダーにあり、変更のたびに GitHub Actions で自動実行されます
（PHP 8.3 / 8.4 × MySQL / MariaDB）。テストは Composer パッケージに含まれないため、自分で実行するにはリポジトリを
クローンしてください。

1. このリポジトリをクローンします。
2. 空の Laravel アプリケーションを作成し、上の「インストール」の手順で `gp247/front` を**クローンから**インストールします。
   `gp247/core` は 3.18.0 以降が必要です（テストの基底クラスは `GP247\Core\Testing` にあります）。テストはアプリケーションの
   `vendor/` にインストールされたコピーを検証するため、先に Composer をクローンに向けます：
   `composer config repositories.front '{"type": "path", "url": "/path/to/front", "options": {"symlink": false}}'`、続けて
   `composer require "gp247/core:^3.18" "gp247/front:*@dev"`。データベースは**テスト専用**のもの（名前が `_test` または
   `_testing` で終わるもの）を使ってください。実データに触れないよう、それ以外のデータベースではテストは実行を拒否します。
   別の名前を使う場合は `GP247_TEST_DATABASE=<データベース名>` で明示してください。
3. アプリケーションのルートで次を実行します。

   ```bash
   GP247_HOST_APP="$PWD" vendor/bin/phpunit -c /path/to/front/phpunit.xml.dist
   ```

ストアフロントを開いた状態で動かす他パッケージやプラグインのテストでは、`GP247\Front\Testing` のヘルパーを再利用できます。
`InteractsWithFrontStore`（テストのためにストアを公開状態にする）、`ConfiguresSeoStore`（ストアの SEO 設定を切り替える）、
`ConfiguresTracking`（トラッキングと同意の設定を準備する）です。

## ドキュメント
詳しいドキュメントは、[ドキュメント](https://gp247.net/en/docs) をご覧ください

## ライセンス
GP247/Front は、[MIT ライセンス](https://opensource.org/licenses/MIT) のもとで提供されるオープンソースソフトウェアです。
