<?php include("../../inc/form_header_c.php"); ?>
<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<!-- title -->
	<title>FSI海洋プラスチック研究</title>
	<!-- meta -->
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="viewport" content="width=device-width,user-scalable=no">
	<meta name="format-detection" content="telephone=no">
	<!-- link -->
	<link href="https://fonts.googleapis.com/css?family=Lato:700&display=swap" rel="stylesheet">
	<link href="/assets/css/normalize.css" rel="stylesheet">
	<link href="/assets/css/common.css" rel="stylesheet">
	<link href="/event/20241223/assets/css/unique.css" rel="stylesheet">
	<!--link href="/favicon.ico" rel="icon"-->
	<script src="../../js/form_setvalue.js" type="text/javascript"></script>
</head>

<body>
	<div id="wrap">
		<header id="gHeader" class="gHeader">
			<div class="branding">
				<div class="wrap">
					<div class="inner">
						<h1 class="siteTitle"><a href="/"><img src="/assets/imgs/logo01.svg" alt="FSI海洋プラスチック研究"></a></h1>
						<a href="https://www.nippon-foundation.or.jp/" target="_blank"><img src="/assets/imgs/hdr-logo01.png" alt="日本財団" class="logo logo01"></a>
						<a href="https://uminohi.jp/umigomi/" target="_blank"><img src="/assets/imgs/hdr-logo02.png" alt="CHANGE FOR THE BLUE" class="logo logo02"></a>
						<a href="https://www.u-tokyo.ac.jp/" target="_blank"><img src="/assets/imgs/hdr-logo03.png" alt="東京大学" class="logo logo03"></a>
						<a href="https://www.u-tokyo.ac.jp/adm/fsi/ja/" target="_blank"><img src="/assets/imgs/hdr-logo04.png" alt="UTokyoFSI" class="logo logo04"></a>
						<ul class="link">
							<li class="contact"><a href="/contact/">Contact</a></li>
							<li class="english"><a href="/en/">English</a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="navigation">
				<div class="btMenu">
					<div class="bar"><span></span></div>
				</div>
				<nav>
					<div class="wrap">
						<ul>
							<li><a href="/"><span class="en">HOME</span><span class="ja">ホーム</span></a></li>
							<li>
								<a href="/#about" class="hasChild"><span class="en">About</span><span class="ja">本プロジェクトについて</span></a>
								<ul>
									<li><a href="/#mission"><span class="en">Mission</span><span class="ja">ミッション</span></a></li>
									<li><a href="/leader.html"><span class="en">From Project Leader</span><span class="ja">ごあいさつ</span></a></li>
									<li><a href="/members.html"><span class="en">Members</span><span class="ja">プロジェクトメンバー</span></a></li>
								</ul>
							</li>
							<li>
								<a href="/research.html" class="hasChild"><span class="en">Research</span><span class="ja">研究内容</span></a>
								<ul>
									<li><a href="/research.html#act01"><span class="en">Act I</span><span class="ja">海洋プラスチックごみの科学的知⾒の充実</span></a></li>
									<li><a href="/research.html#theme01"><span class="en">Act I &gt; テーマ1</span><span class="ja">海洋マイクロプラスチックの実態および挙動の把握</span></a></li>
									<li><a href="/research.html#theme02"><span class="en">Act I &gt; テーマ2</span><span class="ja">海洋マイクロプラスチックの⽣体影響評価</span></a></li>
									<li><a href="/research.html#theme03"><span class="en">Act I &gt; テーマ3</span><span class="ja">プラスチックごみ削減方策に関する総合的研究</span></a></li>
									<li><a href="/research.html#theme04"><span class="en">Act I &gt; テーマ4</span><span class="ja">第1フェーズまでの成果</span></a></li>
									<li><a href="/research.html#act02"><span class="en">Act II</span><span class="ja">研究プラットフォームの構築および情報発信</span></a></li>
								</ul>
							</li>
							<li>
								<a href="/publications.html" class="hasChild"><span class="en">Publications</span><span class="ja">成果のご紹介</span></a>
								<ul>
									<li><a href="/publications.html#journals"><span class="en">JOURNALS</span><span class="ja">ジャーナル</span></a></li>
									<li><a href="/publications.html#books"><span class="en">BOOKS/E-BOOKS</span><span class="ja">書籍</span></a></li>
									<li><a href="/publications.html#others"><span class="en">OTHER PUBLICATIONS</span><span class="ja">その他の成果物</span></a></li>
									<li><a href="/publications.html#award"><span class="en">AWARD</span><span class="ja">受賞</span></a></li>
								</ul>
							</li>
							<li><a href="/news-topics/"><span class="en">News &amp; Topics</span><span class="ja">ニュース・トピックス</span></a></li>
							<li><a href="/columns-reports/"><span class="en">Columns &amp; Reports</span><span class="ja">コラム・レポート</span></a></li>
							<li class="sp contact"><a href="/contact/">Contact</a></li>
							<li class="sp english"><a href="/en/">English</a></li>
						</ul>
					</div>
				</nav>
			</div><!-- /gNav -->
		</header><!-- /header -->

		<!-- ========================================================================== -->

		<main class="main">
			<article>
				<header class="articleHeader">
					<div class="wrap">
						<div class="breadCrumb">
							<ul>
								<li><a href="../index.html">Home</a></li>
								<li>Application</li>
							</ul>
						</div>
						<h2 class="headline01"><span class="en">Application Cancel</span><span class="ja">お申し込みキャンセル</span></h2>
					</div>
				</header>

				<div class="lower contact">
					<div class="wrap">
<p><?php echo(nl2br($config["form_title"])); ?></p>
<p class="txt-1"><?php echo(nl2br($config["information"])); ?></p>

						<p>イベント申込をキャンセルするときは以下にご入力ください。すべて必須項目です。</p>
<?php if($delete) {?>
	<p class="txt-1">このイベントは終了いたしました。</p>
<?php } else{ ?>
						<form action="./form.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="formname" value="<?php echo $config['formname'] ?>"/>
							<input type="hidden" name="mode" value="CANCEL">
							<input type="hidden" name="msgid" value="<?php if(isset($msgid)){echo $msgid;} ?>">
							<dl>
								<dt>申込No. </dt>
								<dd><input type="text" name="item12" required placeholder="申込No.を入力してください"></dd>
								<dt>氏名</dt>
								<dd><input type="text" name="item01" required placeholder="山田　太郎"></dd>
								<dt>氏名カナ</dt>
								<dd><input type="text" name="item02" required placeholder="ヤマダ　タロウ"></dd>
								<dt>ご所属</dt>
								<dd><input type="text" name="item03" required></dd>
								<dt>メールアドレス</dt>
								<dd><input type="email" name="item04" required placeholder="sample@mail.com"></dd>
							</dl>
							<input type="submit" value="送信内容を確認する" class="btContact">
							<input type="hidden" name="action" value="submit">
						</form>
<script type="text/javascript">
<?php echo $jsbuf; ?>
</script>
<?php }?>
					</div>
				</div>
			</article>
		</main><!-- /mainContents -->

		<!-- ========================================================================== -->

		<footer class="gFooter">
			<a href="#wrap" class="pageTop">PAGE TOP</a>
			<div class="wrap">
				<div class="inner">
					<div class="navigation">
						<ul>
							<li><a href="/">HOME</a></li>
							<li><a href="/#about">About</a></li>
							<li><a href="/research.html">Research</a></li>
							<li><a href="/publications.html">Publications</a></li>
							<li><a href="/news-topics/">News &amp; Topics</a></li>
							<li><a href="/columns-reports/">Columns &amp; Reports</a></li>
							<li><a href="/contact/">Contact</a></li>
							<li class="sp"><a href="#">English</a></li>
						</ul>
					</div>
					<div class="contact">
						<div class="logo">
							<picture>
								<source media="(max-width: 750px)" srcset="/assets/imgs/logo01.svg">
								<img src="/assets/imgs/logo02.svg" alt="東京大学-日本財団 FSI海洋プラスチック研究">
							</picture>
						</div>
						<div class="text">本プロジェクトに関するお問い合わせ<br class="pc">
							<span>東京大学 大気海洋研究所 FSI海洋プラスチック 研究事務局</span>
						</div>
						<div class="inner">
							<div class="address">〒277-8564　<br class="pc">千葉県柏市柏の葉5-1-5<br>
								TEL 080-7124-7351</div>
							<div class="mail"><a href="#">メールによるお問い合わせ</a></div>
						</div>
					</div>
				</div>
			</div>
			<div class="sdgs">
				<div class="wrap">私たちは持続可能な開発目標（SDGs）を支援しています。</div>
			</div>
			<div class="copyright">
				<div class="wrap">COPYRIGHT&copy; The University of Tokyo FSI - Nippon Foundation Research Project on Marine Plastics, ALL RIGHTS RESERVEDS.</div>
			</div>
		</footer><!--/footer -->
	</div><!--/wrap -->

	<!-- ========================================================================== -->

	<!-- js -->
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/jquery-migrate-3.1.0.min.js" integrity="sha256-ycJeXbll9m7dHKeaPbXBkZH8BuP99SmPm/8q5O+SbBc=" crossorigin="anonymous"></script>
	<script src="../../../assets/js/jquery.smooth-scroll.min.js"></script>
	<script src="../../../assets/js/platform.js"></script>
	<script src="../../../assets/js/common.js"></script>
	<script src="../assets/js/unique.js"></script>
</body>

</html>