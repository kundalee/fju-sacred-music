<?php
/* Smarty version 4.0.0, created on 2024-05-22 17:06:30
  from '/media/rddd/242438d5-0d71-4648-99bc-7c839d703f11/Rddd/ChihHe-Projects/fju-sacred-music/themes/zh-tw/index.dwt' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.0.0',
  'unifunc' => 'content_664db5965d3041_22869618',
  'has_nocache_code' => true,
  'file_dependency' => 
  array (
    'f04d3f0d2377c73637b58eeedafbdaf143e18832' => 
    array (
      0 => '/media/rddd/242438d5-0d71-4648-99bc-7c839d703f11/Rddd/ChihHe-Projects/fju-sacred-music/themes/zh-tw/index.dwt',
      1 => 1693271825,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:library/page_header.lbi' => 1,
    'file:library/page_footer.lbi' => 1,
  ),
),false)) {
function content_664db5965d3041_22869618 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex,nofollow">
<meta name="wm.env" content="development">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="format-detection" content="telephone=no">
<meta name="keywords" content="<?php echo (($tmp = $_smarty_tpl->tpl_vars['keywords']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>
">
<meta name="description" content="<?php echo preg_replace('!\s+!u', '',(($tmp = $_smarty_tpl->tpl_vars['description']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp));?>
">
<meta name="twitter:title" content="<?php echo smarty_function_breadcrumb_meta(array('main'=>$_smarty_tpl->tpl_vars['shop_title']->value),$_smarty_tpl);?>
">
<meta name="twitter:keywords" content="<?php echo (($tmp = $_smarty_tpl->tpl_vars['keywords']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>
">
<meta name="twitter:description" content="<?php echo preg_replace('!\s+!u', '',(($tmp = $_smarty_tpl->tpl_vars['description']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp));?>
">
<meta name="twitter:site" content="<?php echo smarty_insert_base_url(array(),$_smarty_tpl);?>">
<meta property="og:title" content="<?php echo smarty_function_breadcrumb_meta(array('main'=>$_smarty_tpl->tpl_vars['shop_title']->value),$_smarty_tpl);?>
">
<meta property="og:keywords" content="<?php echo (($tmp = $_smarty_tpl->tpl_vars['keywords']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>
">
<meta property="og:description" content="<?php echo preg_replace('!\s+!u', '',(($tmp = $_smarty_tpl->tpl_vars['description']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp));?>
">
<meta property="og:url" content="<?php echo smarty_insert_base_url(array(),$_smarty_tpl);?>">
<title><?php echo smarty_function_breadcrumb_meta(array('main'=>$_smarty_tpl->tpl_vars['shop_title']->value),$_smarty_tpl);?>
</title>
<base href="<?php echo smarty_insert_base_url(array(),$_smarty_tpl);?>" data-theme="<?php echo (($tmp = $_smarty_tpl->tpl_vars['theme_dir']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>
" data-lang="<?php echo (($tmp = $_smarty_tpl->tpl_vars['lang_code']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>
">
<link href="favicon.ico" rel="icon">
<link href="favicon.ico" rel="shortcut icon">
<link href="favicon-32x32.png" rel="icon" type="image/png" sizes="32x32">
<link href="favicon-16x16.png" rel="icon" type="image/png" sizes="16x16">
<link href="apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180">
<link href="site.webmanifest" rel="manifest">
<link href="themes/zh-tw/assets/css/bootstrap.min.css" rel="stylesheet">
<link href="themes/zh-tw/assets/css/plugins.min.css" rel="stylesheet">
<link href="themes/zh-tw/assets/css/custom.min.css" rel="stylesheet">
<?php if ((($tmp = (defined('DEMO_MODE') ? constant('DEMO_MODE') : null) ?? null)===null||$tmp==='' ? false ?? null : $tmp) == false) {
echo (($tmp = $_smarty_tpl->tpl_vars['stats_code_head']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>

<?php }?>
</head>
<body>
<main>
  <?php $_smarty_tpl->_subTemplateRender("file:library/page_header.lbi", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
  <div class="body-container">
    <div class="page-banner-frame home" data-parallax="scroll" data-image-src="themes/zh-tw/assets/images/page-banner-01.jpg">
      <div class="mask"></div>
    </div>
    <section class="welcome-wrapper">
      <div class="row">
        <div class="col d-none d-md-block"><div class="text-center"><img src="themes/zh-tw/assets/images/sacred-music-logo.png" width="275"></div></div>
        <div class="col">
          <div class="heading-title">
            <div class="welcome">歡迎來到</div>
            <h1>天主教中文聖歌資料庫</h1>
          </div>
          <div class="widget-container">
            <form name="search-form" action="search.php" method="get" role="search">
            <div class="search-form">
              <input placeholder="快速搜尋關鍵字" class="form-control" type="text" name="q">
              <button class="btn" type="submit">
                <i class="fa fa-search" aria-hidden="true"></i>
                <span class="sr-only">Search</span>
              </button>
            </div>
            <div class="py-3 text-right"><a href="advanced.php" class="advanced-search">進階搜尋</a></div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="home-popular-wrapper">
      <div class="global-title-frame">熱門歌曲</div>
      <div class="row">
        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['popular_song']->value, 'item', false, NULL, 'list', array (
));
$_smarty_tpl->tpl_vars['item']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->do_else = false;
?>
        <div class="col-sm-6 col-md-3 list-item">
          <div class="item-frame">
            <div class="hp-picture"><a title="<?php echo htmlspecialchars((($tmp = (($tmp = $_smarty_tpl->tpl_vars['item']->value['zh_song_title'] ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_1'] ?? null : $tmp) ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_2'] ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
" href="<?php echo $_smarty_tpl->tpl_vars['item']->value['url'];?>
"><?php if ($_smarty_tpl->tpl_vars['item']->value['picture_large']) {?><img alt="<?php echo htmlspecialchars((($tmp = (($tmp = $_smarty_tpl->tpl_vars['item']->value['zh_song_title'] ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_1'] ?? null : $tmp) ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_2'] ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
" src="<?php echo $_smarty_tpl->tpl_vars['item']->value['picture_large'];?>
" class="img-fluid"><?php } else { ?><img alt="<?php echo htmlspecialchars((($tmp = (($tmp = $_smarty_tpl->tpl_vars['item']->value['zh_song_title'] ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_1'] ?? null : $tmp) ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_2'] ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
" src="themes/zh-tw/assets/images/default-image-song-645x645.png" class="img-fluid"><?php }?></a></div>
            <div class="hp-title"><a title="<?php echo htmlspecialchars((($tmp = (($tmp = $_smarty_tpl->tpl_vars['item']->value['zh_song_title'] ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_1'] ?? null : $tmp) ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_2'] ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
" href="<?php echo $_smarty_tpl->tpl_vars['item']->value['url'];?>
"><?php echo htmlspecialchars((($tmp = (($tmp = $_smarty_tpl->tpl_vars['item']->value['zh_song_title'] ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_1'] ?? null : $tmp) ?? null)===null||$tmp==='' ? $_smarty_tpl->tpl_vars['item']->value['en_song_title_2'] ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
</a></div>
          </div>
        </div>
        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
      </div>
    </section>
  </div>
  <?php $_smarty_tpl->_subTemplateRender("file:library/page_footer.lbi", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
</main>
<?php echo '<script'; ?>
 async src="themes/zh-tw/assets/js/plugins/require.min.js" data-main="themes/zh-tw/assets/js/require-config.js"><?php echo '</script'; ?>
>
<?php if ((($tmp = (defined('DEMO_MODE') ? constant('DEMO_MODE') : null) ?? null)===null||$tmp==='' ? false ?? null : $tmp) == false) {
echo (($tmp = $_smarty_tpl->tpl_vars['stats_code_body']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp);?>

<?php }?>
</body>
</html><?php }
}
