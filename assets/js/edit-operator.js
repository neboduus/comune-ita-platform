require("jquery"); // Load jQuery as a module

$(document).ready(function () {
  const GROUPS = $("#mainList").find('input.group');
  const CHILDREN = $("#mainList").find('input').not('.group');

  GROUPS.on('change',function(e,isPageLoad){
    let children = $(this).parents('li:first').find('ul').find('input');
    if($(this).is(':checked'))
    {
      if(!isPageLoad)
        children.prop('checked',true);
    }else{
      if(!isPageLoad)
        children.prop('checked',false);
    }
  }).trigger('change',[true]);

  CHILDREN.on('change',function(){
    checkGroups();
  })

  function checkGroups(){
    GROUPS.each((idx,el)=>{
      let children = $(el).parents('li:first').find('ul').find('input');
      if(children.length > 0){
        let countChecked = 0;
        $(children).each((idx,elm) => {
          if($(elm).is(':checked')){
            countChecked++
          }
        });
        if (countChecked === $(children).length){
          $(el).prop('checked',true);
        }else{
          $(el).prop('checked',false);
        }
      }
    })
  }

  checkGroups();

});
