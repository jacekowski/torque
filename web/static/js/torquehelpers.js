function matchCustom(params, data) {
  // If there are no search terms, return all of the data
  if ($.trim(params.term) === '') {
    return data;
  }

  // Do not display the item if there is no 'text' property
  if (typeof data.text === 'undefined') {
    return null;
  }

  // `params.term` should be the term that is used for searching
  // `data.text` is the text that is displayed for the data object
  var str_array = params.term.toLowerCase().split(' ');

  for(var i = 0; i < str_array.length; i++) {
    // Trim the excess whitespace.
    str_array[i] = str_array[i].replace(/^\s*/, "").replace(/\s*$/, "");
    // Add additional code here, such as:
    if (data.text.toLowerCase().indexOf(str_array[i]) === -1) {
      // Return `null` if the term should not be displayed
      return null;

    }
  }
  var modifiedData = $.extend({}, data, true);
  modifiedData.text += ' (matched)';

  // You can return modified objects from here
  // This includes matching the `children` how you want in nested data sets
  return modifiedData;
}

$(document).ready(function(){
  // Activate Chosen on the selection drop down
  $("select#plot_data").chosen({width: "100%"});
  //$("select#id").chosen({width: "100%", enable_split_word_search: true, search_contains: true});
  $("select#id").select2({
    matcher: matchCustom
  });
  // Center the selected element
  // Limit number of multi selects to 2
  $("select#plot_data").chosen({max_selected_options: 2, no_results_text: "Oops, nothing found!"});
  $("select#plot_data").chosen({placeholder_text_multiple: "Choose OBD2 data.."});
  // When the selection drop down is open, force all elements to align left with padding
  $('select#plot_data').on('chosen:showing_dropdown', function() { $('li.active-result').attr('align', 'left');});
  $('select#plot_data').on('chosen:showing_dropdown', function() { $('li.active-result').css('padding-left', '20px');});
});
