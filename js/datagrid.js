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


function show_child_grid(id,url){

  grid_id = '#child_grid_'+id;
  
  if($(grid_id).html() == false){
    iFrameHtml = "<td colspan=\"100\"><iframe src="+url+" style=\"width: 100%; height: 400px; border: 0px;\" seamless/></iframe></td>";

    $(grid_id).html(iFrameHtml);
  }
  
  cell_id = '#child_grid_link_cell_'+id;
  show_link_id = '#show_child_grid_'+id;
  hide_link_id = '#hide_child_grid_'+id;
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