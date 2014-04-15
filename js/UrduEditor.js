///////////////////////////////////////////////////////////////////////////
//  Original Contribution and Major Debugging: Nabeel     //
//  Help Tip Functionality and Debugging: Asif Iqbal (28 March, 2005)//
//  ToolTip Script: Walter Zorn           //
//  Version: 0.0.4
//  Took out the on screen keyboard and put it in an extra file
///////////////////////////////////////////////////////////////////////////
var isIE;
var isGecko;
var isOpera;
var kbNormal=1;
var kbShift=2;    
var kbAlt=3;
var kbCtrl=4;
var kbAltGr=5;
var bToggleFlag=0;
var CurrentKeyboardState=1;
var IsInitialized= false;
var editAreas= new Array();
//var urduEditAreas= new Array();
var _sp;
var urduEditAreas= {};
var onscreenKeyboard;
function _s(c){return String.fromCharCode(c)}
  var currEdit=null;
  var langSel=1;
  var IsUrdu=1;
var langArray=new Array();
  var codes= new Array();
  codes['a']=0x0627;
  codes['b']=0x0628;
  codes['c']=0x0686;
  codes['d']=0x062F;
  codes['e']=0x0639;
  codes['f']=0x0641;
  codes['g']=0x06AF;
  codes['h']=0x06BE;
  codes['i']=0x06CC;
  codes['j']=0x062C;
  codes['k']=0x06A9;
  codes['l']=0x0644;
  codes['m']=0x0645;
  codes['n']=0x0646;
  codes['o']=0x06C1;
  codes['p']=0x067E;
  codes['q']=0x0642;
  codes['r']=0x0631;
  codes['s']=0x0633;
  codes['t']=0x062A;
  codes['u']=0x0626; // hamza yeh
  codes['v']=0x0637;
  codes['w']=0x0648;
  codes['x']=0x0634;
  codes['y']=0x06D2;
  codes['z']=0x0632;
  
  codes['A']=0x0622;
  codes['B']=0x0628;
  codes['C']=0x062B;
  codes['D']=0x0688;
  codes['E']=0x0651;
  codes['F']=0x064D;
  codes['G']=0x063A;
  codes['H']=0x062D;
  codes['I']=0x0670;
  codes['J']=0x0636;
  codes['K']=0x062E;
  codes['L']=0x0628;
  codes['M']=0x064B;
  codes['N']=0x06BA;
  codes['O']=0x06C3;
  codes['P']=0x064F;
  codes['Q']=0x0628;
  codes['R']=0x0691;
  codes['S']=0x0635;
  codes['T']=0x0679;
  codes['U']=0x0621;
  codes['V']=0x0638;
  codes['W']=0x0624;
  codes['X']=0x0698;
  codes['Z']=0x0630;
  
  codes['>']=0x0650;
  codes['<']=0x064E;
  codes[_s(32)]=32;
  codes[_s(13)]=13;
  codes[':']=0x061B;
  codes[';']=0x061B;
  codes[_s(39)]=0x2018;
  codes[_s(34)]=0x201C;
  codes[_s(46)]=0x06D4;
  codes[_s(44)]=0x060C;
  codes['!']= 0x0021;
  codes['?']=0x061F;
  codes[':']=58;
  
  codes['[']=0x0654; // hamza above
  codes[']']=0x0655; // hamza below
  codes['~']=0x0653; // mad above
  codes['^']=0x0652; // sukun
  codes['/']=0x002F; // slash
  codes['L']=0x064C; // do paish
  //codes['|']=0x0200C; // ZWNJ
  codes['+']=0x002B;
  codes['-']=0x002D;
  codes['*']=0x00D7;
  codes[_s(47)]=0x00F7;
  codes[_s(37)]=0x066A;
  codes['(']=0x0028;
  codes[')']=0x0029;
  codes['=']=0x003D;
  codes['0']=0x30;
  codes['1']=0x31;
  codes['2']=0x32;
  codes['3']=0x33;
  codes['4']=0x34;
  codes['5']=0x35;
  codes['6']=0x36;
  codes['7']=0x37;
  codes['8']=0x38;
  codes['9']=0x39;
  var Diacritics='[]{}~';
  
  
  function isDiacritic(sChar)
  {
    if(Diacritics.indexOf(sChar)>=0)
    {
      return true;
    }
    return false;  
  }
     function storeCaret (textEl) {
       if (textEl.createTextRange)
         textEl.caretPos = document.selection.createRange().duplicate();
     }
  function AddText(text)
  {
  
    if(!currEdit) return;
  
    if (currEdit.createTextRange && currEdit.caretPos) {      
      var caretPos = currEdit.caretPos;      
      caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ?
      text + ' ' : text;
      currEdit.focus(caretPos);
    }
    else if (currEdit.selectionStart || currEdit.selectionStart == '0')
    {
      var vTop=currEdit.scrollTop;
      //alert(vTop);
      var startPos = currEdit.selectionStart;
      var endPos = currEdit.selectionEnd;
      currEdit.value = currEdit.value.substring(0, startPos)
              + text
              + currEdit.value.substring(endPos, currEdit.value.length);
      currEdit.focus();
      currEdit.selectionStart = startPos + 1;
      currEdit.selectionEnd = startPos + 1;
      currEdit.scrollTop=vTop;
    }
    else
    {
      currEdit.value += text;
      currEdit.focus(caretPos);  
    }
    }
  
function processKeyup(evt)
{
  if (!currEdit) return;
  evt = (evt) ? evt : ((event) ? event : null);
    if (evt)
  {
    var charCode = (evt.charCode) ? evt.charCode : evt.keyCode;
    
    if(charCode == 17)
    {
      CurrentKeyboardState = kbNormal;
    }
    else if(charCode == 16)
    {
      CurrentKeyboardState = kbNormal;
    }
  }
}
function processKeydown(evt)
{
  
  //if (!langArray[currEdit.id]) return;
  if (!currEdit) return;
  evt = (evt) ? evt : ((event) ? event : null);
    if (evt)
  {
    var charCode = (evt.charCode) ? evt.charCode : evt.keyCode;    
    var idxChar=String.fromCharCode(charCode).toLowerCase();
    /*if(charCode != 17)
        alert(CurrentKeyboardState);*/
    if(charCode == 17)
    {
      CurrentKeyboardState = kbCtrl;
    }
    else if(charCode == 16)
    {
      CurrentKeyboardState = kbShift;
    }
    else if(CurrentKeyboardState == kbCtrl)
    {
      /*if(charCode != 17)
        alert(charCode);*/
      if(charCode==32)
      {
        if(!currEdit.getAttribute('WebpadId'))
        {
          if(langArray[currEdit.name]==1)
          {
            //alert('setting English');
            setEnglish(currEdit.name);
          }
          else
          {
            //alert('setting Urdu');
            setUrdu(currEdit.name);
          }
        }
        else
        {
          if(langArray[currEdit.id]==1)
          {
            //alert('setting English');
            setEnglishById(currEdit.id);
          }
          else
          {
            //alert('setting Urdu');
            setUrduById(currEdit.id);
          }
        }
        
        if(isIE)
        {
          evt.returnValue=false;
          evt.cancelBubble=true;
        }
        else if(isGecko)
        {
          evt.preventDefault();
          evt.stopPropagation();
        }
      }            
    }
    else if(CurrentKeyboardState == kbShift)
    {
      if(charCode==32)
      {
        if(!currEdit.getAttribute('WebpadId'))
        {
          if(langArray[currEdit.name]==1)
          {
            //alert("zwnj");
            AddText(String.fromCharCode(0x200C)); //ZWNJ
            if(isIE)
            {
              evt.returnValue=false;
              evt.cancelBubble=true;
            }
            else if(isGecko)
            {
              evt.preventDefault();
              evt.stopPropagation();
            }
          }
        }
        else
        {
          if(langArray[currEdit.id]==1)
          {
            //alert("zwnj");
            AddText(String.fromCharCode(0x200C)); //ZWNJ
            if(isIE)
            {
              evt.returnValue=false;
              evt.cancelBubble=true;
            }
            else if(isGecko)
            {
              evt.preventDefault();
              evt.stopPropagation();
            }
          }        
        }
      }
    }
  }
}  
  function processKeypresses(evt)
  {
    //alert(currEdit.name);
    if(!currEdit.getAttribute('WebpadId'))
    {
      if (!langArray[currEdit.name]) return;
    }
    else
    {
      if (!langArray[currEdit.id]) return;
    }
      
       evt = (evt) ? evt : (window.event) ? event : null;
     //if(!evt) evt=window.event;
       if (evt)
          {
      var charCode = (evt.charCode) ? evt.charCode :
                    ((evt.keyCode) ? evt.keyCode :
                   ((evt.which) ? evt.which : 0));
                   var whichASC = charCode ; // key's ASCII code
      var whichChar = String.fromCharCode(whichASC); // key's character
      
      if(isIE)
      {
        evt.keyCode= codes[whichChar];
      }
      else if(isGecko)
      {
        if((charCode==13) || (charCode==8) || (charCode==37) || (charCode==39) ||  (charCode==38)|| (charCode==40)|| (charCode==33) || (charCode==34) || (charCode==46) || (charCode==50)  ) return;
        
        if((CurrentKeyboardState == kbShift) || CurrentKeyboardState == kbCtrl)
        {
          if(charCode==32)
          {
            evt.preventDefault();
            evt.stopPropagation();
            return;
          }
        }
                      
        if(isDiacritic(whichChar)) // fix for the AltGr key on German keyboard
        {
          //alert('isDiacritic : '+charCode);
          AddText( String.fromCharCode(codes[whichChar]));
          evt.preventDefault();
          evt.stopPropagation();  
          return;
        }
        
        if(!(CurrentKeyboardState == kbCtrl))
        {          
          //alert('in : '+charCode);
          AddText( String.fromCharCode(codes[whichChar]));
          evt.preventDefault();
          evt.stopPropagation();          
        }
        
                
      }
      else if(isOpera)
      {
        //evt.charCode= codes[whichChar];
        //alert('evt.charCode : '+whichChar);
        if((charCode==13) || (charCode==37) || (charCode==39) ||  (charCode==38)|| (charCode==40)|| (charCode==33) || (charCode==34) || (charCode==46) || (charCode==50)  ) return;
        
        //AddText(whichChar);
        AddText( String.fromCharCode(codes[whichChar]));
        evt.preventDefault();
        evt.stopPropagation();        
      }      
      }          
  }
  
  
function setEditor(evt)
{
  if(isOpera)
  {
    currEdit=window.event.srcElement;
  }
  if(isGecko)
  {
    currEdit=evt.target;    
  }
  else
    currEdit=window.event.srcElement;
}
function setUrduById(idx)
{
  setUrdu(idx, true);
}
function setEnglishById(idx)
{
  setEnglish(idx, true);
}
function setUrdu(idx, byId)
{
  var el;
  if(arguments.length >1)
  {
    if(byId)
      el= document.getElementById(idx);
    else
      el= $(idx);
  }
  else
  {
    el= $(idx);
  }
  
  langArray[idx]=1;
  el.focus(1);
  el.style.backgroundColor="#99FF99";
  if (el.createTextRange)
  {
    var caretPos = el.caretPos;
    el.focus(caretPos);
    currEdit=el;
  }
  else if (el.selectionStart || el.selectionStart == '0')
  {
    var startPos = el.selectionStart;
    el.focus();
    currEdit=el;
    el.selectionStart = startPos + 1;
    el.selectionEnd = startPos + 1;
  }
}
function writeToggleControl(idx, byId)
{
  var strName= idx+"_toggle";
  
  if(arguments.length >1)
  {
    if(byId)
      document.writeln('English<input type="radio" value="English" name="'+strName+'"onclick=\'setEnglishById("'+idx+'")\'>&#1575;&#1585;&#1583;&#1608;<input type="radio" value="Urdu" checked name="'+strName+'" onclick=\'setUrduById("'+idx+'")\'>');    
  }
  else
  {
    document.writeln('English<input type="radio" value="English" name="'+strName+'"onclick=\'setEnglish("'+idx+'")\'>&#1575;&#1585;&#1583;&#1608;<input type="radio" value="Urdu" checked name="'+strName+'" onclick=\'setUrdu("'+idx+'")\'>');
  }
  
  
  //English<input type="radio" value="English" name="toggle" onclick='setEnglish("Editor")'>����<input type="radio" value="Urdu" checked name="toggle" onclick='setUrdu("Editor")'>
}
function setEnglish(idx, byId)
{
  var el;
  if(arguments.length >1)
  {
    if(byId)
      el= document.getElementById(idx);
    else
      el= $(idx);
  }
  else
  {
    el= $(idx);
  }
  
  langArray[idx]=0;
  el.style.backgroundColor="#CCCCFF";
  if (el.createTextRange)
  {
    var caretPos = el.caretPos;
    el.focus(caretPos);
    currEdit=el;
  }
  else if (el.selectionStart || el.selectionStart == '0')
  {
    var startPos = el.selectionStart;
    el.focus();
    currEdit=el;
    el.selectionStart = startPos + 1;
    el.selectionEnd = startPos + 1;
  }
}
/*function toggleLanguage(idx)
{
  if(langArray[idx]==1)
    setEnglish(idx);
  else
    setUrdu(idx);
}*/
function $(strName)
{
  var result=null;
  
  // first look for an id attribute
  
  allInputs= document.getElementsByTagName('input');
    
  for (var i = 0; i < allInputs.length; i++)
  {
    thisInput = allInputs[i];
    if(thisInput.type == 'text')
    {      
      if(thisInput.name == strName)        
        return thisInput;
    }
  }
  
  allTextAreas= document.getElementsByTagName('textarea');
  for (i = 0; i < allTextAreas.length; i++)
  {
    thisTextArea= allTextAreas[i];        
    if(thisTextArea.name == strName)        
      return thisTextArea;    
  }
  return null;
}
function addEvent2(obj, evType, fn){
  
  if (window.opera && obj.addEventListener)
  {
  obj.addEventListener(evType, fn, false);
    return true;
  }  
  else if (obj.addEventListener)
  {
  //alert("on"+evType);
    obj.addEventListener(evType, fn, true);
    return true;
  }
  else if (obj.attachEvent)
  {
   // alert("on"+evType);
    var r = obj.attachEvent("on"+evType, fn);  
    return r;
  
  }
  else
  {
    alert("Handler could not be attached");
  }
}
function makeUrduEditorById(idx, pt)
{
  langArray[idx]=1;
  var el= document.getElementById(idx);
  
  el.setAttribute("WebpadId", idx);
  if(null==el)
    return;
  setAttributes(el, pt);
}
function makeUrduEditor(idx, pt)
{    
  langArray[idx]=1;
  //var el=document.getElementById(idx);  
  //var el=editAreas[idx];
  var el= $(idx);
  
  if(null==el)
    return;
  setAttributes(el, pt);    
}
function setAttributes(el, pt)
{
  el.lang="ur";
  el.dir="rtl";
  el.onFocus= "setEditor(el)";
  el.onclick="storeCaret(el)";
  el.onkeyup="storeCaret(el)";
  el.wrap="soft";
  with(el.style)
  {
    fontFamily="Urdu Naskh Asiatype";
    fontSize=pt;
    backgroundColor="#99FF99";
  }
  
  addEvent2(el , "keypress",  processKeypresses);
  addEvent2(el , "keydown",  processKeydown);
  addEvent2(el , "keyup",  processKeyup);
  addEvent2(el , "focus", setEditor);  
}
/*function Initialize()
{
  
  if(IsInitialized)
  {
    return;
  }
  
  allInputs= document.getElementsByTagName('input');
    
  for (var i = 0; i < allInputs.length; i++)
  {
    thisInput = allInputs[i];
    if(thisInput.type == 'text')
    {      
      editAreas[thisInput.name]= thisInput;  
      thisInput.index= thisInput.name;          
    }
  }
  
  allTextAreas= document.getElementsByTagName('textarea');
  for (i = 0; i < allTextAreas.length; i++)
  {
    thisTextArea= allTextAreas[i];
    editAreas[thisTextArea.name]= thisTextArea;  
    thisTextArea.index= thisTextArea.name;
  }
  IsInitialized= true;
}*/
// pass an array of edit areas and point sizes
function makeUrduEditors(editors)
{
  var strName;
  var iSize;
  
  /*if(!IsInitialized)
  {
    Initialize();
  }*/
  
  for(var xtextEl in editors)
  {
    strName= xtextEl;
    iSize= editors[xtextEl];
    makeUrduEditor(strName, iSize);
  }
}
function makeUrduEditorsById(editors)
{
  var strName;
  var iSize;
  
  for(var xtextEl in editors)
  {
    strName= xtextEl;
    iSize= editors[xtextEl];
    makeUrduEditorById(strName, iSize);
  }
}
function initUrduEditor(settings)
{
  var allInputs, thisInput, attr;
  var allTextAreas, thisTextArea;
  var editors;
  
  var ua = navigator.userAgent.toLowerCase();
  isIE = ((ua.indexOf("msie") != -1) && (ua.indexOf("opera") == -1) && (ua.indexOf("webtv") == -1));
  isGecko = (ua.indexOf("gecko") != -1);
  isOpera= window.opera;
  var _se = document.getElementsByTagName('script');
    for (var i=0; i<_se.length; i++) {
    if (_se[i].src && (_se[i].src.indexOf("UrduEditor.js") != -1) )
    {      
      _x=_se[i].src.indexOf("UrduEditor.js");
      _sp=_se[i].src.substring(0,_x);    
      //alert(_sp);
    }
  }
  if(null!= settings)
  {    
    if(settings.onscreenKeyboard)
    {
      onscreenKeyboard= settings.onscreenKeyboard;
      if(settings.onscreenKeyboard)
      {
        document.writeln('<script language="javascript1.2" type="text/javascript" src="'+_sp+'webpadkeyboard.js"></script>');
      }      
    }    
  }
  
//  document.writeln('<style type="text/css">@import "'+_sp+'UrduEditor.css";</style>');
}