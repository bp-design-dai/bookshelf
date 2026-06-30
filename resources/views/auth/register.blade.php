<h1>Register</h1>

<form method="POST" action="/register">
    @csrf

    <input type="text" name="name" placeholder="名前">
    <input type="email" name="email" placeholder="メールアドレス">
    <input type="password" name="password" placeholder="パスワード">
    <input type="password" name="password_confirmation" placeholder="パスワード確認">

    <button type="submit">登録</button>
</form>