function like_btn_ajax(postID,userID) {
    jQuery.ajax({
        url: like_ajax_url.ajax_url,
        type:'post',
        data:{
            action:'like_btn_ajax_handle',
            pid:postID,
            uid:userID
        },
        success:function (response) {
            console.log(response);
            jQuery("#postLikeAjax span").html(response);
        },
        error(jqXHR, exception)
        {
            console.log(jqXHR.responseText);
        }
    })
}
function dis_like_btn_ajax(postID,userID) {
    jQuery.ajax({
        url: dislike_ajax_url.ajax_url,
        type:'post',
        data:{
            action:'dislike_btn_ajax_handle',
            pid:postID,
            uid:userID
        },
        success:function (response) {
            console.log(response);
            jQuery("#postLikeAjax span").html(response);
        },
        error(jqXHR, exception)
        {
            console.log(jqXHR.responseText);
        }
    })
}
