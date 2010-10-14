function cssParse(){
  this.rawCSS = '';

  // Set raw css to work on
  this.setRawCSS = function(css){
    this.rawCSS = css;
  }

  this.getSelectors = function(){
    potentialSelectors = this.rawCSS.split("{");
    this.CSSSelectors = new Array();
    
    // Handle the first selector specifically, because there will be no } to work with
    
    lineCount = 0;
    for(count = 0; count<potentialSelectors.length; ++count){
      this.CSSSelectors[count] = new Array();
      psSplit = new Array();
      
      // First, find end of previous selector block
      if(count == 0){
        // Handle first selector specifically - end of
        // previous block is at 0
        psSplit[0] = "";
        psSplit[1] = potentialSelectors[count];
      }else{
        psSplit = potentialSelectors[count].split("}");
      }
      // psSplit[0] contains css rules from previous selector to end of previous selector block
      // psSplit[1] contains junk (comments, selectors) from end of previous block to { for current block
        
      
      // Count newlines in previous css rules
      cssRulesNewlines = psSplit[0].count("\n");
      lineCount += cssRulesNewlines;
      
      this.CSSSelectors[count]['startsOnLine'] = lineCount + 1;
      
      if(typeof(psSplit[1]) !== 'undefined'){
        isComment = true; 

        while(isComment == true){
          // Check if this selector starts with a comment
          if(psSplit[1].replace(/[\n||\r||\t||\s]/gi, "").substr(0, 2) == "/*"){
            // if this selector started with a comment, 
            // we'll count newlines until the end of the comment, split it, and go again
            endCommentPos = psSplit[1].indexOf('*/');
            splitAgain = psSplit[1].split('*/', 1);
            commentLines = splitAgain[0].count("\n");
            lineCount += commentLines;
            // Add 2 to endCommentPos for the end comment chars
            psSplit[1] = psSplit[1].substr(endCommentPos+2);
            // Unset splitAgain for next round
            splitAgain = null;

            isComment = true;
          }else{
            isComment = false;
          }
        }

        lineCount += psSplit[1].count("\n");
        // If there is, we're looking for linecount (end of previous block) + newlines + 1 (to account for last line, which won't have a newline showing)
        this.CSSSelectors[count]['startsOnLine'] = lineCount+1;
        this.CSSSelectors[count]['selector'] = psSplit[1];
        // Count up all lines in the actual selector
        //lineCount = psSplit[1].count("\n")+lineCount;
  
      }
    }    
  }
}


String.prototype.count = function(char){
    return this.split(char).length-1;
}

String.prototype.regexIndexOf = function(regex, startpos) {
    var indexOf = this.substring(startpos || 0).search(regex);
    return (indexOf >= 0) ? (indexOf + (startpos || 0)) : indexOf;
}

String.prototype.regexLastIndexOf = function(regex, startpos) {
    regex = (regex.global) ? regex : new RegExp(regex.source, "g" + (regex.ignoreCase ? "i" : "") + (regex.multiLine ? "m" : ""));
    if(typeof (startpos) == "undefined") {
        startpos = this.length;
    } else if(startpos < 0) {
        startpos = 0;
    }
    var stringToWorkWith = this.substring(0, startpos + 1);
    var lastIndexOf = -1;
    var nextStop = 0;
    while((result = regex.exec(stringToWorkWith)) != null) {
        lastIndexOf = result.index;
        regex.lastIndex = ++nextStop;
    }
    return lastIndexOf;
}
