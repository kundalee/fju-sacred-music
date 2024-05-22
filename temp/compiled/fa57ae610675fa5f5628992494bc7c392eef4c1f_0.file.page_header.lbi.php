<?php
/* Smarty version 4.0.0, created on 2024-05-22 17:06:30
  from '/media/rddd/242438d5-0d71-4648-99bc-7c839d703f11/Rddd/ChihHe-Projects/fju-sacred-music/themes/zh-tw/library/page_header.lbi' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.0.0',
  'unifunc' => 'content_664db5965d88d5_46187848',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fa57ae610675fa5f5628992494bc7c392eef4c1f' => 
    array (
      0 => '/media/rddd/242438d5-0d71-4648-99bc-7c839d703f11/Rddd/ChihHe-Projects/fju-sacred-music/themes/zh-tw/library/page_header.lbi',
      1 => 1661915166,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_664db5965d88d5_46187848 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="navbar-mask"></div>
<header id="global-header-wrapper">
  <div class="navbar-fixed">
    <nav class="navbar navbar-expand-xl">
      <div class="container-fluid">
        <div class="navbar-brand"><a title="天主教中文聖歌資料庫" href="./"><img src="themes/zh-tw/assets/images/sacred-music-logo.png" alt="天主教中文聖歌資料庫" height="64"></a></div>
        <div class="navbar-collapse">
          <ul class="navbar-nav">

            <li class="nav-item">
              <a title="歌本專區" class="nav-link" href="book.php">歌本專區</a>
            </li>
            <li class="nav-item">
              <a title="歌曲專區" class="nav-link" href="song.php">歌曲專區</a>
            </li>
            <li class="nav-item">
              <a title="創作者專區" class="nav-link" href="minstrel.php">創作者專區</a>
            </li>
            <li class="nav-item">
              <a title="重點歌曲" class="nav-link" href="recommend.php">重點歌曲</a>
            </li>
            <li class="nav-item">
              <a title="最新消息" class="nav-link" href="blog.php">最新消息</a>
            </li>
            <li class="nav-item">
              <a title="關於我們" class="nav-link" href="about_us.php">關於我們</a>
            </li>
          </ul>
        </div>
        <div class="navbar-others">
          <div class="row gutters-5 justify-content-end align-items-center">
            <div class="col">
              
              <form name="search-form" action="search" method="get" class="search-form">
              <input class="form-control input-search" type="text" name="q" value="<?php echo htmlspecialchars((($tmp = $_REQUEST['q'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp), ENT_QUOTES, 'UTF-8', true);?>
" placeholder="快速搜尋關鍵字...">
              </form>
              
            </div>
          </div>
        </div>
        <div class="navbar-options ml-auto">
          <div class="row no-gutters align-items-center">
            <div class="col-auto"></div>
            <div class="col-auto">
              <button type="button" title="選單開關" class="navbar-toggler navbar-switch">
                <i></i>
                <i></i>
                <i></i>
                <span class="sr-only">網站選單</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </nav>
  </div>
</header><?php }
}
