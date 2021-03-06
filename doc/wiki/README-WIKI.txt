+Wiki 20070831

rep2ex 20070823の機能追加パッチです。
とりあえず作っただけのα版です。

主な機能
BEあぼーん…BEのNG/あぼーん機能
sambaタイマー…次に書き込めるまでの時間を表示
携帯でも番号にSPM…spm.phpへのリンクを付ける
DAT取得プラグイン…各種DAT保管サイトからDATを取得
リンクプラグイン…YouTube・ニコニコ動画等のリンクプラグインを汎用化したもの
置換画像URL…JaneStyleのImageViewURLReplace.datとほぼ同じ(ImageCache2が必要)
置換ワード…レスの内容の置換+変数展開
スレッドあぼーんワード…題名に特定の内容が入ったスレッドをあぼーん
imepita対応…imepitaにおいて403エラーが出たら取得できるまで繰り返す
あぼーんワードフォームに削除チェックボックス…ありそうでなかったので
バグ修正…POSTバグ・Imagemagickを使った場合のバグ・実装されていない機能がユーザ設定に表示されているミス
機能の先取り…k_clip_unique_idの実装

注意点
いくつかの設定フォームが用意されていないので、対応するファイルを手動で編集して下さい。
スレッドあぼーんワードはまともに動かないと思います。
sambaタイマーもまだ不備があるようで、VIPくらいでしか動かないようです。(簡単なミスなのですぐに修正できると思います)
IDストーカーポップアッププラグインの画像は用意してないので必死チェッカーポップアッププラグインの画像と同じです。

インストール方法
上書き

設定項目
con_user_def_wiki.inc.php参照。
デフォルトで全て有効です。
DAT取得プラグイン・リンクプラグイン・置換画像URLはサンプル参照。

BENGあぼーん
特定のBEをあぼーんする機能です。
SPMと設定管理にBENGあぼーんに関する項目が追加されます。
なお、判定条件は正規表現または完全一致です。
大文字小文字無視は設定項目こそあるものの無効になります。

sambaタイマー
次に書き込めるまでの時間を表示します。
フルブラウザなら携帯でも表示できます。

リンクプラグイン
URLをHTMLコードに変換するプラグイン。
YouTubeプレビューなどに使います。

置換画像URL
画像をImageCache2で取得できるようにURLを変換します。
また、URLが推定できないサイトの場合はソースを展開して取得します。
JaneStyleのImageViewURLReplace.datとほぼ同じです。
$EXTRACTを使うとかなり遅くなるので注意。

置換ワード
レスの内容を正規表現で置換します。
また、変数をその内容に展開します。

DAT取得プラグイン
各種DAT保管サイトからDATを取得します。
存在を確認すると、レスポンスコードが表示されます。
主なレスポンスコード
200:取得可能
403:アクセス制限(そのプラグインは無効)
404:DATがない
それ以外:URLのミス

あぼーんワードフォームに削除チェックボックス
チェックすると削除します。

付属ファイル
doc/data/p2_plugin_dat.txt	DAT取得プラグインのサンプル(みみずん検索等)
doc/data/p2_plugin_link.txt	リンクプラグインのサンプル(YouTube・ニコニコ動画・ニフニフ動画)
doc/data/p2_replace_imageurl.txt	置換画像URLのサンプル(注:JaneStyleのまとめほぼそのまま)
doc/data/p2_replace_name.txt	置換ワードの名前のサンプル
doc/data/p2_replace_date.txt	置換ワードの日付その他のサンプル
doc/data/p2_replace_mail.txt	置換ワードのメールその他のサンプル

バグ修正
POSTでb=kを認識しないバグ修正(書き込みフォームなど)
Imagemagickを使った場合にエラーが出るであろうバグ修正
いくつか存在しない機能が設定フォームにあるのを消した(まだあるかも)

主な注意点
・$EXTRACTは当然時間がかかります
・置換画像URLで$OOOKIEと$EXTRACT={URL}は未対応($COOKIEが使えないためpic.toがプレビューできないかも)
・置換ワードの各内容の取り扱いには注意して下さい
　ID: ?[0-9A-Za-z/.+]{8,11}はIDポップアップに変換されます
　BE:\d+はBEポップアップに変換されます

JaneStyleのImageViewURLReplace.datを流用する場合の修正箇所
・bmp対応の削除
・改行コードの変更(そのままでも問題ないと思う)
・リンクプラグインと被るものの削除(サンプルの場合だとニコニコ動画とYouTuBeが被る)
・$COOKIEや$EXPAND=の削除(対応してません)
・コメントの削除(無視されます)

付属プラグイン
plugin以下の各ディレクトリのドキュメントを参照して下さい。
必死チェッカーポップアッププラグイン(hissi/readme.txt)
みみずんID検索ポップアッププラグイン(mimizun/readme.txt)
IDストーカーポップアッププラグイン(stalker/readme.txt)