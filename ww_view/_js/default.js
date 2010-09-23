/*
	this is the default javascript file for wicked words
	it contains functions relating to the comment form and image popups
	it can be overwritten by placing a default.js file in your theme's _js/ folder
*/

  // load jquery from google if not found
  
    if (typeof jQuery == 'undefined') { 
      var head = document.getElementsByTagName("head")[0];
      script = document.createElement('script');
      script.id = 'jQuery';
      script.type = 'text/javascript';
      script.src = 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js';
      head.appendChild(script);
    }
  
  // this writes the reply link within the comment div
  
	$(document).ready(function(){ 
		
		// this appends a 'reply to' link to comments
		
		$('div#comments_wrapper div.comment').each(function(i){
			var id = $(this).attr("id");
			var comment_id = id.replace('comment_wrapper_','');
			if($(this).hasClass('reply_comment')) {
				// do nothing
			} else {
				$(this).append('<p class="reply_link" id="reply_to_' + comment_id + '"><a href="javascript:void(0);">reply to this</a></p>');
			}
		})
		
	});
	
	// this sets the reply to text on the comment form
	
		$(function(){
		    $('p.reply_link').click(function(){
				// get id of comment being replied to
				var id = $(this).attr("id");
				var comment_id = id.replace('reply_to_','');
			// get text of the comment being replied to
				var wrapper = $('div#comment_wrapper_' + comment_id)
				var name = $('div#comment_wrapper_' + comment_id + ' span.comment_name').html()
				var date = $('div#comment_wrapper_' + comment_id + ' span.comment_date').html()
				var body = $('div#comment_wrapper_' + comment_id + ' p.comment_body').html()
			// set reply id in hidden form field
				var form_reply_id = $('#reply_id')
				form_reply_id.val(0)
				form_reply_id.val(comment_id)
			// set reply text
				var reply_el = $('#reply_text')
				reply_el.html('')
				var reply_header  = '<p class="reply_header">'+name+' - '+date+'</p>'
				var reply_body    = '<p class="reply_body">'+body+'</p>'
				var reply_cancel  = '<p class="cancel_reply">(click to cancel reply)</p>';
				reply_el.append(reply_header);
				reply_el.append(reply_body);
				reply_el.append(reply_cancel);
				var label = $('#comment_form label[for="comment_body"]')
				label.html('Your reply:')				
		    });
		});
			

    // this cancels the reply (resets the hidden field and clears the reply text)
    
    	$(function(){
		    $('div#reply_text').click(function(){
				var form_el = $('#reply_id')
				form_el.val(0)
				var reply_el = $('#reply_text')
				reply_el.html('')
				var label = $('#comment_form label[for="comment_body"]')
				label.html('Your comment:')
		    });
		});

	// full size image popup
		
		$(function(){
		    $('img.image_thumb').click(function(){
				var full_img = new Image();
				full_img.src = this.src.replace('/thumbs','');
				w = full_img.width + 20;
				h = full_img.height + 20;
				window.open(full_img.src,'image','width='+w+',height='+h+',location=no,scrollbars=no,toolbar=no,status=no,titlebar=no');
		        return false;
		    });
		});