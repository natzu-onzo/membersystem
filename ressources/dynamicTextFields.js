
var fieldCounter = 0;
function addDetailField(divName) {
    var newDiv = document.createElement('div');
    newDiv.innerHTML = "Detalje " + (fieldCounter + 1) + " <br><input type='text' name='detaljer[]' id='detail" + fieldCounter + "'>";
    //    newDiv.innerHTML = "Detalje " + (fieldCounter + 1) + " <br><input type='text' name='detalje" + fieldCounter + "' id='detail" + fieldCounter + "'>";
    document.getElementById(divName).appendChild(newDiv);
    fieldCounter++;
}

function addDetailValueField(divName, detailName) {
    var newDiv = document.createElement('div');
    newDiv.innerHTML = detailName + ": <br><input type='text' name='detailValue[]' >";
    document.getElementById(divName).appendChild(newDiv);
    
    }
// clears all added value fields
function removeDetailValueFields(divName) {
    var node = document.getElementById(divName);
    while (node.hasChildNodes()) {
        node.removeChild(node.lastChild);
        }
    }
    
$(function() {
    select = document.getElementById('type_select');
    select.onchange = function() {
        var id = select.options[select.selectedIndex].value;
        $.post('admin/hent_lossalg_detaljer', {id: select.options[select.selectedIndex].value},
               function(response) {
                   removeDetailValueFields('detailValue');
                   var response = JSON.parse(response);
                   for (i = (response.length - 1); i >= 0 ; i--) {
                       var detailName = response[i].detail_name;
                       addDetailValueField('detailValue', detailName);
                       console.log(response[i].detail_name);
                   }
               }
              );
    }
})

