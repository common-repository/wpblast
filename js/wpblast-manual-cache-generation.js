isGeneratingCache = true;
if (jQuery) {
    jQuery(document).ready(function ($) {
        var crawlError = [];
        function wpblastGenerateCacheItem(i) {
            var errorMsg =
                crawlError.length > 0
                    ? '<br/><br/>' + 'Error while generating cache: ' + '<br/>' + crawlError.join('<br/>')
                    : '';
            if (i >= toCrawl.length) {
                // finished
                isGeneratingCache = false;
                if (crawlError.length === 0) {
                    jQuery('.wpblast-result-generate-cache').removeClass('notice-warning').addClass('notice-success');
                    jQuery('.wpblast-result-generate-cache').html(
                        'Cache has been generated.<br/>Cache generated: ' + toCrawl.length + '/' + toCrawl.length,
                    );
                } else {
                    jQuery('.wpblast-result-generate-cache').html(
                        'Cache has been generated with some errors.<br/><br/>Cache generated: ' +
                            (toCrawl.length - crawlError.length) +
                            '/' +
                            toCrawl.length +
                            '<br/>' +
                            'Cache error: ' +
                            crawlError.length +
                            '/' +
                            toCrawl.length +
                            errorMsg,
                    );
                }
            } else {
                // next cache generation
                var keepOpenMsg = '<br/><br/><i>Please keep this page opened until the end of the generation.</i>';
                jQuery('.wpblast-result-generate-cache').html(
                    'Generating cache for: ' +
                        toCrawl[i] +
                        '<br/>' +
                        'Pending cache generation: ' +
                        i +
                        '/' +
                        toCrawl.length +
                        errorMsg +
                        keepOpenMsg,
                );

                var treatError = function () {
                    crawlError.push(toCrawl[i]);
                    jQuery('.wpblast-result-generate-cache').removeClass('notice-warning').addClass('notice-error');
                    wpblastGenerateCacheItem(i + 1);
                };
                var restUrl =
                    '/wp-json/wpblast/v1/generateCacheItem?wpblast_nonce=' +
                    wpblast_nonce +
                    '&url=' +
                    encodeURI(toCrawl[i]); // fallback rest url
                if (
                    typeof wpBlastSettings !== 'undefined' &&
                    wpBlastSettings.restUrls !== null &&
                    wpBlastSettings.restUrls !== undefined
                ) {
                    if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                        try {
                            var url = new URL(wpBlastSettings.restUrls.generateCacheItem);
                            url.searchParams.append('wpblast_nonce', wpblast_nonce);
                            url.searchParams.append('url', encodeURI(toCrawl[i]));
                            restUrl = url.toString();
                        } catch (e) {
                            // Fallback url
                            restUrl =
                                wpBlastSettings.restUrls.generateCacheItem +
                                '?wpblast_nonce=' +
                                wpblast_nonce +
                                '&url=' +
                                encodeURI(toCrawl[i]);
                        }
                    } else {
                        // Fallback url
                        restUrl =
                            wpBlastSettings.restUrls.generateCacheItem +
                            '?wpblast_nonce=' +
                            wpblast_nonce +
                            '&url=' +
                            encodeURI(toCrawl[i]);
                    }
                }
                jQuery.ajax({
                    url: restUrl,
                    headers: {
                        'X-WP-Nonce': wpblast_nonce,
                        'content-type': 'application/json',
                    },
                    method: 'GET',
                    success: function (data) {
                        if (data) {
                            wpblastGenerateCacheItem(i + 1);
                        } else {
                            // error
                            treatError();
                        }
                    },
                    error: treatError,
                });
            }
        }
        if (toCrawl.length === 0) {
            isGeneratingCache = false;
            jQuery('.wpblast-result-generate-cache').html(
                'No url found to crawl.<br/>Please wait for the detection of your sitemap.',
            );
        } else {
            wpblastGenerateCacheItem(0); // start generation
        }
    });
} else {
    console.error('jQuery is necessary to generate cache');
}
