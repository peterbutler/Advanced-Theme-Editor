jQuery(document).ready(function($) {
  // Handle editor setup
  if(editorType == 'text'){
    $("#code").linedtextarea();
    $("#code").keydown(function(event){
      checkTab((event));
    });
  }


  // Display Functions
  // Full screen
  var fullScreen = false;
  $("#fullScreen").click(function(){
    if(fullScreen){
      fullScreen = false;
      $("#editor").css({
      	position:'relative',
      	top: 0,
      	left: 0,
      	width: '100%',
      	height: '100%'
      });
      $("#wpwrap").css({overflow:'auto',height:'auto'});
    }else{
      fullScreen = true;
      $("#wpwrap").css({height:'100%',overflow:'hidden'});
      $("#editor").css({
      	position:'fixed',
      	top: 0,
      	left: 0,
      	width: '100%',
      	height: '100%'
      });
    }
    editorWindowResize();
    previewWindowResize();
    codeWindowResizeWidth();

  });

  // Only parse CSS if necessary
  if(editFile['type'] == 'css'){
    // ===============
    // = CSS PARSING =
    // ===============
    cssParse = new cssParse();
    cssParse.setRawCSS($("#code").val());
    cssParse.getSelectors();
    var lineClass = '';
    for(count = 0; count< cssParse.CSSSelectors.length; ++count){
      if(lineClass == 'alt'){
        lineClass = '';
      }else{
        lineClass = 'alt';
      }
      $("ul#helperWindowCSS").append("<li class='"+lineClass+"'><a href='#' name='"+cssParse.CSSSelectors[count]['selector']+"' alt='"+cssParse.CSSSelectors[count]['startsOnLine']+"'>"+cssParse.CSSSelectors[count]['selector']+"</a></li>");
    }
    // IF we're dealing with a css file, change "preview" to "save"
    $("#actionPreview").val("save");
  }else{
    // Display message about only parsing css files
    $("ul#helperWindowCSS").append("<li class='noLink message'>This is not a css file</li>");
  }
  
    $('#selectorPath li a').live('click', function(){
      highlightElements($(this).attr('name'));
      position = $(this).position();
      rightOffset = 80-($("#codeWindowTop").width()-position.left-$(this).width()-16);
      $("#codeWindowTop").animate({
        right: rightOffset
      }, 200);
    });

  function highlightElements(selector){
    $("#previewWindow").contents().find(".highlightDiv").remove();
    $("#previewWindow").contents().find(selector).each(function(){
      offset = $(this).offset();

      if($(window).scrollTop() != 0){
        offset.top += $("#previewWindow").contents().scrollTop();
        offset.top -= $(window).scrollTop();
      }
      
      width  = $(this).outerWidth();
      height = $(this).outerHeight();
      
      paddingTop    = parseInt($(this).css('padding-top'));
      paddingBottom = parseInt($(this).css('padding-bottom'));
      paddingLeft   = parseInt($(this).css('padding-left'));
      paddingRight  = parseInt($(this).css('padding-right'));
      
      marginTop    = parseInt($(this).css('margin-top'));
      marginBottom = parseInt($(this).css('margin-bottom'));
      marginLeft   = parseInt($(this).css('margin-left'));
      marginRight  = parseInt($(this).css('margin-right'));
            
      $marginHighlight  = $("<div class='highlightDiv'></div>");
      $paddingHighlight = $("<div class='paddingHighlightDiv highlightDiv'></div>");
      $contentHighlight = $("<div class='contentHighlightDiv highlightDiv'></div>");
      
      $marginHighlight.width(width+marginLeft+marginRight);
      $marginHighlight.height(height+marginTop+marginBottom);
      $marginHighlight.css('top', (offset.top-marginTop-1)+"px");
      $marginHighlight.css('left', (offset.left-marginLeft-1)+"px");
      
      $paddingHighlight.width(width);
      $paddingHighlight.height(height);
      $paddingHighlight.css('top', (offset.top-2)+"px");
      $paddingHighlight.css('left', (offset.left-2)+"px");
      
      
      $contentHighlight.width(width-paddingLeft-paddingRight);
      $contentHighlight.height(height-paddingTop-paddingBottom);
      $contentHighlight.css('top', (offset.top+paddingTop-1)+"px");
      $contentHighlight.css('left', (offset.left+paddingLeft-1)+"px");
      
      
      $marginHighlight.css('border', '1px solid rgb(0, 148, 255)');
      $marginHighlight.css('position', 'absolute');
      
      $paddingHighlight.css('background', 'rgba(0,148,255,0.15)');
      $paddingHighlight.css('position', 'absolute');
      $paddingHighlight.css('border', '2px solid rgb(0, 148, 255)');

      $contentHighlight.css('position', 'absolute');
      $contentHighlight.css('border', '1px dashed rgb(0, 148, 255)');

      $("#previewWindow").contents().find("body").append($marginHighlight);
      $("#previewWindow").contents().find("body").append($paddingHighlight);
      $("#previewWindow").contents().find("body").append($contentHighlight);
    });
    // add "selected" to selectorlist item
    $("ul#selectorPath li a").each(function(){
      if($(this).attr('name') == selector){
        $(this).addClass('selected');
      }else{
        $(this).removeClass('selected');
      }
    });
  }


  // ==========================
  // = HANDLE WINDOW RESIZING =
  // ==========================

  // Initially
    editorWindowResize();
    previewWindowResize();
    codeWindowResizeWidth();
  // On window resize
  $(window).resize(function() {
    editorWindowResize();
    previewWindowResize();
    codeWindowResizeWidth();
  });

  function editorWindowResize(){
    $("#editor").css('height', function() {
      if(fullScreen){
        topPadding = 1;
      }else{
        topPadding = 100;
      }
      editorWindowHeight = $(window).height()-topPadding;
      if(editorWindowHeight < $("#codeWindow").height() +50){
        return editorWindowHeight = $("#codeWindow").height() +50;
      }
      return editorWindowHeight;
    });
  }

  function previewWindowResize(){
    $("#previewWindow").css('height', function() {
      return $("#editor").height()-$("#editorBlock").height()-27;
    });
  }

  function codeWindowResizeWidth(){
    $("#codeWindow").css('width', function() {
      return $("#editorBlock").width()-202;
    });
    $("textarea#code").css('width', function() {
      return $("#editorBlock").width()-323;
    });
  }
  function codeWindowResizeHeight(newHeight){
    $("#editorBlock").css('height', newHeight);
    $("#helperWindow ul:not(ul>li>ul)").css('height', newHeight-58);
    $("textarea#code").css('height', newHeight-50);
    $(".linedwrap .lines").css('height', newHeight-50);
    previewWindowResize();

  }
/*
  $("#editorBlock").resizable({
      handles: 'n',
      start: function () {
          $("#iframeContainer").each(function (index, element) {
              var d = $('<div class="iframeCover" style="z-index:99;position:absolute;width:100%;top:0px;left:0px;height:' + $(element).height() + 'px"></div>');
              $(element).append(d);
          });
      },
      resize: function(){ codeWindowResizeHeight($("#editorBlock").height()); },
      containment: '#editor',
      stop: function () {
//          $('.iframeCover').remove();

          $("#editorBlock").css('position', 'relative');
          $("#editorBlock").css('top', 0);
          $("#editorBlock").css('left', 0);

          editorWindowResize();
      }
  });
*/
  // Resize code window
  $('#codeWindowSizeUp').click(function(){
    codeWindowResizeHeight($("#editorBlock").height()+100);
  });
  $('#codeWindowSizeDown').click(function(){
    codeWindowResizeHeight($("#editorBlock").height()-100);
  });

  // ============================
  // = PREVIEW WINDOW FUNCTIONS =
  // ============================

  $("#previewWindow").load(function(){
    // Replace external stylesheet with conents of edit window
    $found = $("#previewWindow").contents().find("head link[href$="+editFile['name']+"]");
    $found.replaceWith("<style id='insertedStyle'>"+$("#code").val()+"</style>");
    if($found.length < 1){
      // Stylesheet not included here
    }
    // Handle highlighting individual elements
    $("#previewWindow").contents().find("*").mousedown(function(event){  
        $("#previewWindow").contents().find(".highlightDiv").remove();
        event.stopPropagation();
    });
    $("#previewWindow").contents().find("*").mouseup(function(){
      currentNode = this;
      selectorPath = "";
      fullSelector = "";
      selectorArray = new Array();
      count = 0;
      while(currentNode.nodeName != "#document"){
        selectorArray[count] = getSelector(currentNode);
        currentNode = currentNode.parentNode;
        ++count;
      }
      for(elementCount = selectorArray.length-1; elementCount > -1; --elementCount){
        fullSelector += selectorArray[elementCount]+" ";
        selectorPath += '<li><a href="#" name="'+fullSelector+'"';
        if(elementCount == 0){
          selectorPath += ' class="selected" ';
        }
        selectorPath += '>'+selectorArray[elementCount]+"</a></li>\r\n";
      }
      // Highlight this element
      highlightElements(fullSelector);
      $("#codeWindowTop").css("right", "80px");
      $("#selectorPath").html(selectorPath);
      return false;
    });
    
    // prevent clicks from going through
    $("#previewWindow").contents().find("*").click(function(){
      return false;
    });

  });


  $("#code").keyup(function(){
    // Change contents of preview window
    $("#previewWindow").contents().find("#insertedStyle").replaceWith("<style id='insertedStyle'>"+$("#code").val()+"</style>");
    // update overlay (if there is one)
   highlightElements($("#selectorPath li a.selected").attr("name"));
  });


  // ======================
  // = HELPER WINDOW TABS =
  // ======================
  var currentHelperWindow = '';
  // Get current helper window
  if(currentHelperWindow == ''){
    currentHelperWindow = $("#helperWindow .selected").attr('id');
  }
  // Remove currently selected tab
  $("#fileTypeTab a").click(function(){
    // Unhighlight old tab selector
    $("#fileTypeTab a").removeClass('selected');
    // Unhighlight new tab selector
    $(this).addClass('selected');

    // Hide old tab
    $("#helperWindow ul").removeClass('selected');
    switch($(this).attr('id')){
      case 'CSSTab':
        $("#helperWindowCSS").addClass('selected');
        //editor.setParser('CSSParser');
        break;
      case 'HTMLTab':
        $("#helperWindowHTML").addClass('selected');
        //editor.setParser('XMLParser');
        break;
      case 'PHPTab':
        $("#helperWindowPHP").addClass('selected');
        //editor.setParser('PHPParser');
        break;
      case 'filesTab':
        $("#helperWindowFiles").addClass('selected');
        //editor.setParser('PHPHTMLMixedParser');
        break;
      case 'themeHistTab':
        $("#helperWindowThemeHist").addClass('selected');
        break;
      case 'fileHistTab':
        $("#helperWindowFileHist").addClass('selected');
        break;
    }

    // Switch current helper window
    currentHelperWindow = $("#helperWindow .selected").attr('id');
    return false;
  });

  // ================================
  // = MISC HELPER WINDOW FUNCTIONS =
  // ================================
  $("#helperSearchInput").focus(function(){
    if($(this).hasClass('default')){
      $(this).val('');
      $(this).removeClass('default');
    }
  });
  $("#helperSearchInput").blur(function(){
    if($(this).val() == ''){
      $(this).val('Search');
      $(this).addClass('default');
    }
  });
  $("#helperSearchInput").keyup(function(){
    $("#helperWindow ul li a").hide();
    $("#helperWindow ul li a[name*="+$(this).val()+"]").show();
  });


  // File window folder open/close
  $("#helperWindowFiles li.folder").click(function(event){
    event.stopPropagation();
    $(this).children('ul').slideToggle();
  });

  // Prevent choosing a new file from opening/closing a folder
  $("#helperWindowFiles li:not(.folder)").click(function(event){
    event.stopPropagation();
  });

  currentFolders = editFile['name'].split('/');
  currentFolder = "";
  for(count = 0; count< currentFolders.length; ++count){
    if(currentFolder == ""){
      currentFolder = currentFolders[count];
    }else{
      currentFolder = currentFolder+'/'+currentFolders[count];
    }
    $(".folder[title="+currentFolder+"] ul").show();
  }

  // CSS SCROLL TO SELECTOR
  $("#helperWindowCSS a").live('click', function(){
    scrollToLine = $(this).attr('alt');
    $('textarea#code').animate({ 'scrollTop': (scrollToLine-1)*15 });

  });

    // ========================
    // = HELPER WINDOW SEARCH =
    // ========================
    // Get current helper window

  // ================
  // = ACTION LINKS =
  // ================
  // Preview
/*
  $("#actionPreview").click(function(){
    $("#previewWindow").attr({src: $("#previewWindow").attr("src")+"/about/"});
  });
*/

  // Draggable/droppable folder movement
  $("#helperWindowFiles .folder").droppable({
    drop: function(event, ui) {
      // unset removeMe flag as child is still inside parent
      ui.helper.removeMe = true;
    },
    greedy:     true,
    hoverClass: 'drophover'
  });
  $("#helperWindowFiles li").draggable({ 
    scroll:            true, 
    scrollSensitivity: 20,
    axis:              "y",
    revert:            "invalid",
    revertDuration:    100,
    zIndex:            2700,
    opacity:           .7,
    start: function(event, ui) {
      ui.helper.removeMe = false;
    },
    stop: function(event, ui) {
      if (ui.helper.removeMe) {
        //ui.helper.slideUp('fast');
      }
    }
  });
  
  $( ".folder" ).bind( "drop", function(event, ui) {
        event.preventDefault();
        event.stopPropagation();
        from = $(ui.draggable).attr('title');
        to = $(this).attr('title');
        moveFile(editFile['theme'], from, to);
        ui.draggable.fadeOut('fast');
        $(this).append(ui.draggable);
  });


function moveFile(theme, from, to){
  $.ajax({
    url: ajaxurl,
    type: "POST",
    data: "action=ateMoveFile&theme="+theme+"&from="+from+"&to="+to,
    success: function(data) {

    }
  });
}

});


// ==================
// = MISC FUNCTIONS =
// ==================
function getSelector(node){
  // GET NODENAME
  nodename = node.nodeName.toLowerCase();
  // GET ID
  if(jQuery(node).attr('id') != ""){
    idSelector = "#"+jQuery(node).attr('id');
  }else{
    idSelector = "";
  }

  // GET CLASS
  if(jQuery(node).attr('class') != ""){
    classSelector = "."+jQuery(node).attr('class').replace(/ /g, ".");
  }else{
    classSelector = "";
  }
  return nodename+idSelector+classSelector;
}
// From http://ajaxian.com/archives/handling-tabs-in-textareas
// Set desired tab- defaults to four space softtab
var tab = "    ";
function checkTab(evt) {
    var t = evt.target;
    var ss = t.selectionStart;
    var se = t.selectionEnd;
    // Tab key - insert tab expansion
    if (evt.keyCode == 9) {
        evt.preventDefault();
                
        // Special case of multi line selection
        if (ss != se && t.value.slice(ss,se).indexOf("\n") != -1) {
            // In case selection was not of entire lines (e.g. selection begins in the middle of a line)
            // we ought to tab at the beginning as well as at the start of every following line.
            var pre = t.value.slice(0,ss);
            var sel = t.value.slice(ss,se).replace(/\n/g,"\n"+tab);
            var post = t.value.slice(se,t.value.length);
            t.value = pre.concat(tab).concat(sel).concat(post);
                    
            t.selectionStart = ss + tab.length;
            t.selectionEnd = se + tab.length;
        }
                
        // "Normal" case (no selection or selection on one line only)
        else {
            t.value = t.value.slice(0,ss).concat(tab).concat(t.value.slice(ss,t.value.length));
            if (ss == se) {
                t.selectionStart = t.selectionEnd = ss + tab.length;
            }
            else {
                t.selectionStart = ss + tab.length;
                t.selectionEnd = se + tab.length;
            }
        }
    }
            
    // Backspace key - delete preceding tab expansion, if exists
   else if (evt.keyCode==8 && t.value.slice(ss - 4,ss) == tab) {
        evt.preventDefault();
                
        t.value = t.value.slice(0,ss - 4).concat(t.value.slice(ss,t.value.length));
        t.selectionStart = t.selectionEnd = ss - tab.length;
    }
            
    // Delete key - delete following tab expansion, if exists
    else if (evt.keyCode==46 && t.value.slice(se,se + 4) == tab) {
        evt.preventDefault();
              
        t.value = t.value.slice(0,ss).concat(t.value.slice(ss + 4,t.value.length));
        t.selectionStart = t.selectionEnd = ss;
    }
    // Left/right arrow keys - move across the tab in one go
    else if (evt.keyCode == 37 && t.value.slice(ss - 4,ss) == tab) {
        evt.preventDefault();
        t.selectionStart = t.selectionEnd = ss - 4;
    }
    else if (evt.keyCode == 39 && t.value.slice(ss,ss + 4) == tab) {
        evt.preventDefault();
        t.selectionStart = t.selectionEnd = ss + 4;
    }
}
 


