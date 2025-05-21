<!-- 淡入淡出的 功能  -->
<script>
    /*
    masg 內容 
    type success / error
    stayTime 執行秒數
    */
    // masg 內容 type success / error
    function toast_btn ( masg = '' , type = 'success' , stayTime = 500 ){
        var clonedElement = $('#share_Toast').clone();
        clonedElement.text(masg);
        clonedElement.removeAttr('id'); // 修改 id，避免重複
        switch (type) {
          case 'success':
            clonedElement.addClass('toastalert-success');
            break;
          case 'error':
            clonedElement.addClass('toastalert-error');
            break;
          default:
        }
        $('body').append(clonedElement);
        clonedElement.fadeToggle(500, function() {
          setTimeout(function() {
            clonedElement.fadeToggle(500, function() {
              clonedElement.remove(); // 從 DOM 中移除元素
            });
          }, stayTime);
        });
    }
</script>
