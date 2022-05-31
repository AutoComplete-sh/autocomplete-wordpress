jQuery( function ( $ ) {
  var ajax;

  if (typeof ajax_object !== 'undefined') {
    ajax = ajax_object;
  }

  updateBalance();

  $( '.autocomplete-enter-api-key-box a' ).on( 'click', function ( e ) {
    e.preventDefault();

    var div = $( '.enter-api-key' );
    div.show( 500 );
    div.find( 'input[name=key]' ).focus();

    $( this ).hide();
  } );

  $( '#autocomplete-job-output').on('click', function ( e ) {
    copyToClipboard($('#autocomplete-job-output span').html());
    alertSuccess('Job output copied to clipboard!');
  });

  $( '#autocomplete-job-input').on('click', function ( e ) {
    copyToClipboard($('#autocomplete-job-input span').html());
    alertSuccess('Job input copied to clipboard!');
  });

  function alertError(msg='') {
    return swal("AutoComplete Error", msg, "error");
  }

  function alertSuccess(msg ='') {
    return swal('AutoComplete Success', msg, 'success');
  }

  function alertWarning(msg='') {
    return swal('AutoComplete Warning', msg, 'warning');
  }

  function updateBalance() {
    if (ajax) {
      let errMsg = 'Unable to fetch account details.';
      $.ajax({
        type: 'POST',
        url: ajax.ajax_url,
        data: {
          'action': 'fetch_account_details',
        },
        datatype: 'json',
        success: function (response) {
          let data = JSON.parse(response) ?? {};
          if (data.status && data.status !== 200) {
            if (data.message) {
              errMsg = data.message;
            }
            return alertError(errMsg);
          }
          if (data.username) {
            $('#autocomplete-username').html(data.username);
            $('#autocomplete-balance').html(data.balance);
          }
        }, error: function (response) {
          alertError(errMsg)
        },
      });
    }
  }

  function showSpinner()
  {
    $('#autocomplete .inside').prepend('<div id="autocomplete-spinner"><i class="autocomplete-spin"></i><span>Running job (be patient, complex jobs can take some time)...</span></div>');
  }

  function hideSpinner()
  {
    $('#autocomplete-spinner').remove();
  }

  function copyToClipboard(text) {
    let $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
  }

  $('#autocomplete-submit').on('click', function ( e ) {
    e.preventDefault();

    var input = "";
    $('.is-root-container  p[role="document"]').each(function(k,v) {
      input = input + v.innerText + "\n";
    });

    if (input.length < 1) {
      alertError('A Paragraph block is required.');
      return;
    }

    input = input.trim();
    let tokens = $('#autocomplete-tokens').val();
    let temperature = $('#autocomplete-temperature').val();
    let readability = $('#autocomplete-readability').is(':checked');

    if (tokens < 1 || tokens > 2048) {
      alertError('Tokens out of range. (1 - 2048)');
      return;
    }

    if (temperature > 1.0 || temperature < 0.1) {
      alertError('Temperature out of range. (0.1 - 1.0)');
      return;
    }

    if (!input.length) {
      alertError('You must type something in your Paragraph or Code block.');
      return;
    }

    if (ajax) {
      let errMsg = 'Oops! Something went wrong...';
      $.ajax({
        type: 'POST',
        url: ajax.ajax_url,
        data: {
          'action': 'text_generate',
          'input': input,
          'output_tokens': tokens,
          'optimize_readability': readability,
          'temperature': temperature
        },
        datatype: 'json',
        beforeSend: function () {
          $('#autocomplete-job-output-container').addClass('autocomplete-hidden');
          showSpinner();
        },
        complete: function () {
          hideSpinner();
        },
        success: function (response) {
          let data = JSON.parse(response);
          if (data.status && data.status !== 200) {
            if (data.message) {
              errMsg = data.message;
            }
            return alertError(errMsg);
          }

          let cost = data.cost ?? 0;

          $('#autocomplete-job-cost span').html(cost);

          let output = data.output.replace(/\\/g,"").trim();
          if (output.length > 0) {
            let combined = data.combined.replace(/\\/g,"").trim();
            $('#autocomplete-job-input span').html(data.input.trim());
            $('#autocomplete-job-output span').html(data.output.trim());
            /*
             * TODO: automatically insert into post body
              $('.wp-block-post-title').focus();
              $block.html(combined.trim().replaceAll('\n', '<br data-rich-text-line-break="true">'));
              $('.is-root-container.block-editor-block-list__layout').click();
            */
            alertSuccess(combined);
          } else {
            alertWarning('No output detected. Try adjusting the input and or the number of tokens used. If the problem persists please contact support.');
          }

          $('#autocomplete-job-output-container').removeClass('autocomplete-hidden');
        }, error: function (response) {
          alertError(errMsg);
        },
      }).then(function () {
        updateBalance();
      });
    }
  });

  $('#autocomplete .handlediv').html('<span aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="components-panel__arrow" role="img" aria-hidden="true" focusable="false"><path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"></path></svg></span>');
  $('#autocomplete .postbox').addClass('closed');

  $('.postbox-header').on('click', function ( e ) {
    let $path = $('#autocomplete button.handlediv svg path');
    if ($('#autocomplete .inside').is(':visible')) {
      $path.attr('d', 'M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z');
    } else {
      $path.attr('d', 'M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z');
    }
  });
});
