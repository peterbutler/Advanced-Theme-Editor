<div class="wrap">
  <h2 style="float:left">Advanced Theme Editor</h2>
  <div style="float:left; color:#333; font-size:10px; margin-top:10px;">
    Follow <a href="http://twitter.com/codesessions">@Codesessions</a> on twitter for updates<br>
    Please email all bugs, features, and threats to <a href="mailto:bugs@codesessions.net">bugs@codesessions.net</a>
  </div>
  <div style="clear:both"></div>
  <div id="editor">
    <div id="previewWindowTopper">
      <h3>Theme: <?php echo $theme; ?></h3>
      <a href="#" id="fullScreen">Fullscreen</a>
      <form action="" method="GET">
        <input type="hidden" name="page" value="advanced-theme-editor">
        <input type="submit" value="Go">
        <select name="editTheme" id="chooseTheme">
          <?php foreach($themes as $selectTheme): ?>
          <option value="<?php echo $selectTheme['Name']; ?>" <?php selected($selectTheme['Name'], $theme); ?>><?php echo $selectTheme['Name']; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="chooseTheme">
          Switch Themes:
        </label>
      </form>
    </div>
    <form action="<?php echo $submissionURL; ?>" method="post">
      <input type="hidden" name="theme" value="<?php echo $theme; ?>">
      <input type="hidden" name="name"  value="<?php echo $editFile['name']; ?>">
      <input type="hidden" name="path"  value="<?php echo $editFile['path']; ?>" >
      <div id="iframeContainer">
      <iframe id="previewWindow" src ="<?php echo ateGetPreviewURL($editFile['path']); ?>" width="100%"></iframe>
      </div>
      <div id="editorBlock">
        <div id="editorBlockTop">
          <div id="codeWindowResize">
            <a href="#" id="codeWindowSizeUp">+</a>
            <a href="#" id="codeWindowSizeDown">-</a>
          </div>
          <div id="currentFile">
            <?php echo $editFile['name']; ?>
          </div>
          <ul id="actions">
            <li><input type="submit" name="action" id="actionPreview" value="preview" /></li>
            <li><input type="submit" name="action" id="actionGoLive" value="go live" /></li>
          </ul>
        </div>
        <div id="fileTypeTab">
          <ul>
            <li><a href="#" id="filesTab">Files</a></li>
            <li><a href="#" id="CSSTab">CSS</a></li>
            <li><a href="#" id="fileHistTab">File History</a></li>
            <li><a href="#" id="themeHistTab">Theme History</a></li>
  
          </ul>
        </div>
        <div id="helperWindow">
          <div id="helperSearch"><input type="text" name="helperSearch" id="helperSearchInput" class="default" value="Search"></div>
          <ul id="helperWindowFiles" class="selected">
            <?php ateDisplayFiles($editableFiles, $theme); ?>
  
          </ul>
          <ul id="helperWindowCSS">
            <!-- Populated by javascript -->
          </ul>
          <ul id="helperWindowFileHist">
            <?php ateDisplayFileHist($editFile['id']); ?>
          </ul>
          <ul id="helperWindowThemeHist">
            <?php ateDisplayThemeHist($theme); ?>
          </ul>
        </div>
        <div id="codeWindow" class="<?php echo $editorType; ?>Editor">
          <div id="codeWindowTop">
            <ul id="selectorPath">
            </ul>
          </div>
          <div id="hiliteCode">
            <?php if($editorType == 'image'): ?>
            <img src="<?php echo $imageSRC; ?>" alt="<?php echo $editFile['name']; ?>" />
            <?php else: ?>
            <textarea id="code" name="newContent"><?php echo $content; ?></textarea>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
