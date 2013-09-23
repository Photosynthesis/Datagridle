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

function highlightRow(row_id,default_color){
  var hlbg = '#aaddaa';
  if (document.getElementById(row_id).bgColor == hlbg){
    document.getElementById(row_id).bgColor = default_color;
  }else{
    document.getElementById(row_id).bgColor = hlbg;
  }
}


// Runs the "Save and continue" field for editing
function contButton(val){
  document.getElementById('continue_edit').value=val;
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


function show_child_grid(id){
  grid_id = '#child_grid_'+id;
  cell_id = '#child_grid_link_cell_'+id;
  show_link_id = '#show_child_grid_'+id;
  hide_link_id = '#hide_child_grid_'+id;
  $(grid_id).toggle('slow');
  $(show_link_id).toggle();
  $(hide_link_id).toggle();
}

function show_child_grid_popup(url){
  child = window.open(url,'child_grid','height=400,width=600,left=20,top=20,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no,status=yes').focus();
}