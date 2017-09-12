if(!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(needle) {
        for(var i = 0; i < this.length; i++) {
            if(this[i] === needle) {
                return i;
            }
        }
        return -1;
    };
}

String.prototype.trim = String.prototype.trim || function trim() { return this.replace(/^\s\s*/, '').replace(/\s\s*$/, ''); };

// Bind the save keystroke for save and continue
$(document).keydown(function(e) {
    if ((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
    {
        e.preventDefault();
        var input = $("<input>", { type: "hidden", name: "continue", value: "on" }); $('#edit_form').append($(input));

        $('#edit_form').submit();

        return false;
    }
    return true;
});


// Delete confirmation
function confirmSubmit(item_num){
  var agree=confirm("Are you sure you'd like to delete item " + item_num + " from the table?");
  if (agree){
  	return true ;
  }else{
  	return false ;
	}
}

function submitform(){
  document.update.submit();
}


function highlightRow(row_id,unselected_class){
  var row = document.getElementById(row_id);

  if (row.className == 'selected_row'){
    row.className = unselected_class;
  }else{
    row.className = 'selected_row';
  }
}


// Runs the "Save and continue" field for editing
function contButton(val){
  var input = $("<input>", { type: "hidden", name: "continue", value: val }); $('#edit_form').append($(input));
  alert('conButton called');
}

function saveCallback(){
  var input = $("<input>", { type: "hidden", name: "continue", value: "on" }); $('#edit_form').append($(input));
  $('#edit_form').submit();
}

function multicheckUpdate(field_id,option){

  hiddenField = document.getElementById(field_id);

  if(hiddenField.value){

    hiddenFieldValue = hiddenField.value;
    options = hiddenFieldValue.split(', ');

    optionIndex = options.indexOf(option);

    //alert('Option values:"'+options+'" Length of options:'+options.length);
    //alert('Option value:"'+option+'"');
    //alert('Option index:'+optionIndex);

    if(optionIndex != -1){
      options.splice(optionIndex,1);
    }else{
      newIndex = options.length;
      options[newIndex] = option;
    }

    newHiddenFieldValue = options.join(', ');
    hiddenField.value = newHiddenFieldValue;

  }else{
    hiddenField.value = option;
  }
}


function showhide_div(id) {
  if (document.getElementById(id).style.display == "block"){
     document.getElementById(id).style.display = "none";
  }else{
     document.getElementById(id).style.display = "block";
  }
}


function show_child_grid(id,table,url){

  grid_id = '#child_grid_'+table+'_'+id;

  if($(grid_id).html() == false){
    iFrameHtml = "<td colspan=\"100\"><iframe src="+url+" style=\"width: 100%; height: 400px; border: 0px;\" seamless/></iframe></td>";

    $(grid_id).html(iFrameHtml);
  }

  cell_id = '#child_grid_link_cell_'+table+'_'+id;
  show_link_id = '#show_child_grid_'+table+'_'+id;
  hide_link_id = '#hide_child_grid_'+table+'_'+id;
  $(grid_id).toggle('slow');
  $(show_link_id).toggle();
  $(hide_link_id).toggle();
}

function show_child_grid_popup(url,other_params){

  if(!other_params){
    other_params = {}
  }

  height = other_params.height || 400;
  width = other_params.width || 600;
  left = other_params.left || 20;
  top = other_params.top || 20;

  child = window.open(url,'child_grid','height='+height+',width='+width+',left='+left+',top='+top+',resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no,status=yes').focus();

}


function ajaxEdit(parent_el,fid,id){

  field = fields[fid];
  if(field.type.substr(0, 6) == 'select' || field.type.substr(0, 12) == 'staticselect'){
      el = document.createElement('select');

      parent_el.parentElement.style.padding = 0;
      el.style.width = parent_el.parentElement.offsetWidth;
      el.style.height = parent_el.parentElement.offsetHeight;

      el.name = field.name;
      el.dataset.fid = fid;
      el.dataset.rowId = id;

      el.addEventListener ("change",ajaxSave, false);
      options = field.options;
      for (key in options) {
        var opt = new Option(options[key],key);
        el.options.add(opt);
      }

      el.value = parent_el.dataset.value;
      parent_el.innerHTML = '';
      parent_el.appendChild(el);
      parent_el.parentElement.style.width = el.style.width;

  }else if(field.type == 'textarea'){
    el = document.createElement('textarea');

    el.style.padding = parent_el.parentElement.style.padding;
    parent_el.parentElement.style.padding = 0;
    el.style.width = parent_el.parentElement.offsetWidth;
    el.style.height = parent_el.parentElement.offsetHeight;

    el.value = parent_el.dataset.value;
    el.name = field.name;
    el.dataset.fid = fid;
    el.dataset.rowId = id;
    el.addEventListener ("blur",ajaxSave, false);
    parent_el.innerHTML = '';
    parent_el.appendChild(el);
    parent_el.parentElement.style.width = el.style.width;
  }else{
    el = document.createElement('input');
    el.type = "text";
    el.value = parent_el.dataset.value;

    el.style.padding = "3px 8px 3px 8px "; //parent_el.parentElement.style.padding;
    parent_el.parentElement.style.padding = 0;
    el.style.width = parent_el.parentElement.offsetWidth;
    el.style.height = parent_el.parentElement.offsetHeight;

    el.name = field.name;
    el.dataset.fid = fid;
    el.dataset.rowId = id;
    el.addEventListener ("blur",ajaxSave, false);
    el.onBlur = "ajaxSave(this)";
    parent_el.innerHTML = '';
    parent_el.appendChild(el);
    parent_el.parentElement.style.width = el.style.width;
  }

  function ajaxSave(triggerEvent){
    field_el = triggerEvent.target;
    fid = field_el.dataset.fid;
    rowId = field_el.dataset.rowId;
    parent_el = field_el.parentElement;
    var params = gpfx+'action=ajax_save&'+gpfx+'fid=' + fid + '&'+gpfx+'value=' + field_el.value + '&'+gpfx+'id=' + rowId;
    parent_el.id = fid+rowId;
    parent_el.parentElement.style.padding = field_el.style.padding;
    parent_el.parentElement.style.width = "";
    parent_el.innerHTML = "Saving...";
    var ajaxSaveReq = $.ajax({
      url: thisUrl,
      data: params
    });

    ajaxSaveReq.done(function( resultJSON ) {
      console.log(resultJSON);
      result = JSON.parse(resultJSON);
      parent_el.dataset.value = result.value;
      if(fields[fid].type == "select" || fields[fid].type == "staticselect"){
        displayValue = fields[fid]['options'][result.value];
      }else{
        displayValue = result.value;
      }
      $("#"+fid+rowId).html(displayValue);
    });

    ajaxSaveReq.fail(function( jqXHR, textStatus ) {
      alert( "Request failed: " + textStatus );
    });

  }

}

function titleToUrl(inputFieldId,outputFileId){
    // when the the value of inputFieldId changes, convert it to a url-safe slug and spit it into outputFileId if(outputFileId is empty)else(confirm overwrite)
    // defult element ids: edit_title and edit_url

    if(inputFieldId !== ""){
      var title_el = document.getElementById(inputFieldId);
    }
    if(title_el == null){
      console.log('title_el is null. Defulting to edit_title');
      var title_el = document.getElementById('edit_title');
      console.log(title_el);
    }
    if(outputFileId !== ""){
      var url_el = document.getElementById(inputFieldId);
    }
    if(url_el == null){
      var url_el = document.getElementById('edit_url');
    }
    var title_el = document.getElementById('edit_title');
    var url_el = document.getElementById('edit_url');

    title_el.onchange = function(){
      if(title_el !== null && url_el !== null && title_el.value !== ""){
        slug = title_el.value.trim().replace(/[ ]/g, '-').replace(/[^a-zA-Z0-9-_]/g, '').toLowerCase();
        console.log(slug);
         if(url_el.value !== ""){
           if(confirm('Would you like to to update the url to: <b>'+slug+'</b>? \n(Changing an existing url could break inbound links.)')){
            url_el.value = slug;
          }
        }else{
          url_el.value = slug;
        }
      }
    }
  }
