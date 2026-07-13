# BookShelf

BookShelfは、書籍の登録・レビュー・お気に入り・ランキング機能を持つ書籍レビューアプリです。

模擬案件として、要件確認、設計、実装、正常系確認、異常系確認、テスト、Pint、Git管理までを意識して作成しています。

---

## アプリケーション概要

ユーザーは書籍を登録し、レビューを投稿できます。  
また、お気に入り登録やレビューへのいいね、レビュー平均点によるランキング表示もできます。

基本機能に加えて、書籍情報を取得・登録・更新・削除できる公開APIも実装しています。

---

## 使用技術

| 技術 | バージョン |
| --- | --- |
| Laravel | 10.50.2 |
| PHP | 8.5.4 |
| MySQL | 8.4.8 |
| Composer | 2.9.5 |
| Node.js | 24.14.1 |
| npm | 11.12.1 |
| Docker | Laravel Sail |

---

## 環境構築

### 1. リポジトリを取得

```bash
git clone https://github.com/bp-design-dai/bookshelf.git
cd bookshelf
```

### 2. Dockerコンテナを起動

```bash
docker compose up -d
```

### 3. 依存関係をインストール

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test npm install
```

### 4. 環境設定ファイルを作成

```bash
cp .env.example .env
```

`.env` のDB設定例です。

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 5. アプリケーションキーを生成

```bash
docker compose exec laravel.test php artisan key:generate
```

### 6. マイグレーションを実行

```bash
docker compose exec laravel.test php artisan migrate
```

### 7. フロントエンドを起動

```bash
docker compose exec laravel.test npm run dev
```

---

## URL

| 種別 | URL |
| --- | --- |
| アプリ | http://localhost |
| 書籍一覧 | http://localhost/ |
| ランキング | http://localhost/ranking |
| お気に入り一覧 | http://localhost/favorites |
| 公開API | http://localhost/api/v1/books |

---

## 実装機能

### 認証機能

- 会員登録
- ログイン
- ログアウト

### 書籍機能

- 書籍一覧表示
- 書籍詳細表示
- 書籍登録
- 書籍編集
- 書籍削除

### レビュー機能

- レビュー登録
- レビュー編集
- レビュー削除
- 1ユーザーにつき1書籍1レビュー

### ジャンル機能

- ジャンル一覧表示
- ジャンル詳細表示
- ジャンル登録
- ジャンル編集
- ジャンル削除
- 書籍との紐付け

### お気に入り・いいね機能

- 書籍のお気に入り追加・解除
- お気に入り一覧表示
- レビューへのいいね追加・解除

### ランキング機能

- レビュー平均点によるランキング表示
- レビューがない書籍はランキング対象外

### 公開API

- 書籍一覧取得
- 書籍詳細取得
- 書籍登録
- 書籍更新
- 書籍削除

---

## 認可

- 書籍の編集・削除は登録者のみ可能
- レビューの編集・削除は投稿者のみ可能
- 未ログインユーザーは認証が必要な画面へアクセス不可

---

## API仕様

### エンドポイント一覧

| メソッド | URL | 内容 |
| --- | --- | --- |
| GET | /api/v1/books | 書籍一覧取得 |
| POST | /api/v1/books | 書籍登録 |
| GET | /api/v1/books/{book} | 書籍詳細取得 |
| PUT/PATCH | /api/v1/books/{book} | 書籍更新 |
| DELETE | /api/v1/books/{book} | 書籍削除 |

### 書籍登録APIのリクエスト例

```json
{
  "user_id": 1,
  "title": "API Test Book",
  "author": "API Author",
  "isbn": "9784000000099",
  "published_date": "2024-07-08",
  "description": "Created by API.",
  "image_url": null,
  "genres": [1]
}
```

### APIバリデーション

| 項目 | ルール |
| --- | --- |
| user_id | 必須、整数、usersテーブルに存在 |
| title | 必須、文字列、255文字以内 |
| author | 必須、文字列、255文字以内 |
| isbn | 必須、13桁、ユニーク |
| published_date | 必須、日付 |
| description | 任意、文字列、1000文字以内 |
| image_url | 任意、URL、2048文字以内 |
| genres | 必須、配列、1件以上3件以内 |
| genres.* | 整数、genresテーブルに存在 |

---

## データベース構成

主なテーブルは以下です。

| テーブル | 内容 |
| --- | --- |
| users | ユーザー |
| books | 書籍 |
| genres | ジャンル |
| reviews | レビュー |
| favorites | お気に入り |
| review_likes | レビューいいね |
| book_genre | 書籍とジャンルの中間テーブル |

---

## テスト

### 全体テスト

```bash
docker compose exec laravel.test php artisan test
```

直近の実行結果です。

```text
Tests: 2 deprecated, 32 passed, 84 assertions
```

PHP 8.5環境により `PDO::MYSQL_ATTR_SSL_CA` の非推奨警告が表示されますが、テスト自体は通過しています。

### Pint

```bash
docker compose exec laravel.test ./vendor/bin/pint
```

直近の実行結果です。

```text
PASS 95 files
```

---

## 補足

- 公開APIは現時点では認証なしで実装しています。
- POST APIでは `user_id` をリクエストで受け取ります。
- PUT/PATCH APIでは `user_id` は変更しません。
- 書籍削除時は、関連するレビュー、お気に入り、ジャンル紐付けも削除されます。
- 応用要件として、検索・絞り込み・ソート・Google Books API・Sanctum認証APIなどを追加予定です。