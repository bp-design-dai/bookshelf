<h1>Login</h1>

<form method="POST" action="/login">
    @csrf

    <input type="email" name="email" placeholder="メールアドレス">
    <input type="password" name="password" placeholder="パスワード">

    <button type="submit">ログイン</button>
</form>