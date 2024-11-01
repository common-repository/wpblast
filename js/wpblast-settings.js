(function () {
    var isGeneratingCache = false;

    if (jQuery) {
        function wpBlastHtmlEntities(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        /**
         * Dynamic Update sync
         */
        var wpBlastTreatError = function (text, location) {
            selector =
                location === 'registerForm' ? '.wpblast-register-account-error' : '.wpblast-result-dynamic-update';
            if (text !== '') {
                jQuery(selector).removeClass('notice-warning').addClass('notice-error').html(text).show();
            } else {
                jQuery(selector).hide();
            }
        };

        function updatePluginData() {
            var restUrl = '/wp-json/wpblast/v1/updatePluginData?wpblast_nonce=' + wpblast_nonce; // fallback rest url
            if (
                typeof wpBlastSettings !== 'undefined' &&
                wpBlastSettings.restUrls !== null &&
                wpBlastSettings.restUrls !== undefined
            ) {
                if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                    try {
                        var url = new URL(wpBlastSettings.restUrls.updatePluginData);
                        url.searchParams.append('wpblast_nonce', wpblast_nonce);
                        restUrl = url.toString();
                    } catch (e) {
                        // Fallback url
                        restUrl = wpBlastSettings.restUrls.updatePluginData + '?wpblast_nonce=' + wpblast_nonce;
                    }
                } else {
                    // Fallback url
                    restUrl = wpBlastSettings.restUrls.updatePluginData + '?wpblast_nonce=' + wpblast_nonce;
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
                    if (!data) {
                        wpBlastTreatError('Error while updating plugin data.');
                    }
                },
                error: function () {
                    wpBlastTreatError('Error while updating plugin data.');
                },
            });
        }

        function updateUserAccount() {
            var restUrl = '/wp-json/wpblast/v1/updateUserAccount?wpblast_nonce=' + wpblast_nonce; // fallback rest url
            if (
                typeof wpBlastSettings !== 'undefined' &&
                wpBlastSettings.restUrls !== null &&
                wpBlastSettings.restUrls !== undefined
            ) {
                if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                    try {
                        var url = new URL(wpBlastSettings.restUrls.updateUserAccount);
                        url.searchParams.append('wpblast_nonce', wpblast_nonce);
                        restUrl = url.toString();
                    } catch (e) {
                        // Fallback url
                        restUrl = wpBlastSettings.restUrls.updateUserAccount + '?wpblast_nonce=' + wpblast_nonce;
                    }
                } else {
                    // Fallback url
                    restUrl = wpBlastSettings.restUrls.updateUserAccount + '?wpblast_nonce=' + wpblast_nonce;
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
                    if (!data) {
                        wpBlastTreatError('Error while updating user account.');
                    }
                },
                error: function () {
                    wpBlastTreatError('Error while updating user account.');
                },
            });
        }

        function updateCrawlerList() {
            var restUrl = '/wp-json/wpblast/v1/updateCrawlerList?wpblast_nonce=' + wpblast_nonce; // fallback rest url
            if (
                typeof wpBlastSettings !== 'undefined' &&
                wpBlastSettings.restUrls !== null &&
                wpBlastSettings.restUrls !== undefined
            ) {
                if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                    try {
                        var url = new URL(wpBlastSettings.restUrls.updateCrawlerList);
                        url.searchParams.append('wpblast_nonce', wpblast_nonce);
                        restUrl = url.toString();
                    } catch (e) {
                        // Fallback url
                        restUrl = wpBlastSettings.restUrls.updateCrawlerList + '?wpblast_nonce=' + wpblast_nonce;
                    }
                } else {
                    // Fallback url
                    restUrl = wpBlastSettings.restUrls.updateCrawlerList + '?wpblast_nonce=' + wpblast_nonce;
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
                    if (!data) {
                        wpBlastTreatError('Error while updating crawler list.');
                    }
                },
                error: function () {
                    wpBlastTreatError('Error while updating crawler list.');
                },
            });
        }

        jQuery(document).ready(function ($) {
            setTimeout(updatePluginData, 200);
            setTimeout(updateUserAccount, 400);
            setTimeout(updateCrawlerList, 600);
        });

        /**
         * Update tab user
         */
        jQuery(document).ready(function ($) {
            if (
                typeof wpBlastSettings !== 'undefined' &&
                wpBlastSettings.formView !== null &&
                wpBlastSettings.formView !== undefined
            ) {
                var wpBlastFormView = wpBlastSettings.formView;
                switch (wpBlastFormView) {
                    case 'user-non-connected':
                    case 'user-connected':
                        $('#wpblast_home-tab').show();
                        $('#wpblast_crawler-tab').show();
                        $('#wpblast_cache-tab').show();
                        if (wpBlastSettings.userAccount !== undefined && wpBlastSettings.userAccount !== null) {
                            if (wpBlastSettings.userAccount['email']) {
                                $('.user_account_email td').html(wpBlastSettings.userAccount['email']);
                            }
                            if (wpBlastSettings.userAccount['domainsToDisplay']) {
                                $('.user_account_domains td').html(wpBlastSettings.userAccount['domainsToDisplay']);
                            }
                            if (wpBlastSettings.userAccount['plansToDisplay']) {
                                $('.user_account_plans td').html(wpBlastSettings.userAccount['plansToDisplay']);
                            }
                        }
                        if (wpBlastSettings.cache && wpBlastSettings.cache.upgradeMessage) {
                            $('.wpblast-progress-upgrade').html(wpBlastSettings.cache.upgradeMessage);
                        }
                        $('.user_account_email').show();
                        $('.user_account_domains').show();
                        $('.user_account_plans').show();
                        $('.timeout').show();
                        $('#wpblast_home .submit').show();
                        $('#wpblast_dashboard .submit').hide();

                        // Start getting cache status
                        if (isGeneratingCache || wpBlastSitemap.length === 0) wpBlastGetSitemapFrequency = 1000;
                        else wpBlastGetSitemapFrequency = 5000;
                        if (window.location.search.indexOf('debug') === -1) {
                            wpBlastGetSitemapInterval = setInterval(wpBlastGetSitemap, wpBlastGetSitemapFrequency);
                        }
                        wpBlastGetSitemap();
                        break;
                    case 'error':
                        $('#wpblast_home-tab').hide();
                        $('#wpblast_crawler-tab').hide();
                        $('#wpblast_cache-tab').hide();
                        $('.user_account_email').hide();
                        $('.user_account_domains').hide();
                        $('.user_account_plans').hide();
                        $('.timeout').hide();
                        $('#wpblast_dashboard .submit').hide();
                        $('#wpblast_home .submit').hide();
                        wpBlastTreatError('Error while updating plugin data.'); // display error notification
                        break;
                    default:
                        break;
                }
            }
            if (typeof wpBlastSettings !== 'undefined' && wpBlastSettings.debugView === true) {
                $('.server').show();
                $('.website').show();
                $('.username').show();
                $('.password').show();
                $('#wpblast_home .submit').show();
            } else {
                $('.server').hide();
                $('.website').hide();
                $('.username').hide();
                $('.password').hide();
            }
        });

        /*
            Hydrate registration form
        */
        jQuery(document).ready(function ($) {
            if (
                wpBlastSettings !== undefined &&
                wpBlastSettings !== null &&
                wpBlastSettings.registrationForm !== undefined &&
                wpBlastSettings.registrationForm !== null
            ) {
                $('.wpblast-register-account-firstname').val(
                    wpBlastSettings.registrationForm.firstName ? wpBlastSettings.registrationForm.firstName : '',
                );
                $('.wpblast-register-account-lastname').val(
                    wpBlastSettings.registrationForm.lastName ? wpBlastSettings.registrationForm.lastName : '',
                );
                $('.wpblast-register-account-email').val(
                    wpBlastSettings.registrationForm.email ? wpBlastSettings.registrationForm.email : '',
                );
                $('.wpblast-register-account-password').val(
                    wpBlastSettings.registrationForm.password ? wpBlastSettings.registrationForm.password : '',
                );

                // display error message if any
                if (
                    wpBlastSettings.registrationForm.registrationError !== undefined &&
                    wpBlastSettings.registrationForm.registrationError !== null
                ) {
                    if (wpBlastSettings.registrationForm.registrationError.openRegistrationForm === true) {
                        toggleRegisterForm();
                    }
                    if (
                        wpBlastSettings.registrationForm.registrationError.message &&
                        wpBlastSettings.registrationForm.registrationError.message !== ''
                    ) {
                        wpBlastTreatError(wpBlastSettings.registrationForm.registrationError.message, 'registerForm');
                    }
                }
            }
        });

        /**
         * Manage cache and dashboard section
         */

        var wpBlastGetSitemapInterval;
        var wpBlastGetSitemapFrequency;
        var wpBlastItemPerPage = 10;
        var wpBlastSitemapOffset = 0;
        var wpBlastSitemapLimit = wpBlastItemPerPage;
        var wpBlastSitemap = [];
        var wpBlastHomeItem = {};
        var wpBlastLoadingStep = 1;
        var wpBlastDetails = [];
        var wpBlastSitemapUpdatedItems = [];
        var wpBlastSitemapItemsToUpdateScore = [];
        var wpBlastIsLoadingSitemap = false;
        var types = ['raw', 'blast'];
        var devices = ['mobile', 'desktop'];
        var wpBlastPagesSelected = {};
        var wpBlastSettingsMaxPagesToBlast = 1; // default in case non-existent
        var wpBlastSettingsUserHasGuestPlan = true; // default in case non-existent
        if (
            typeof wpBlastSettings !== 'undefined' &&
            wpBlastSettings.userAccount !== null &&
            wpBlastSettings.userAccount !== undefined
        ) {
            if (
                wpBlastSettings.userAccount.features !== null &&
                wpBlastSettings.userAccount.features !== undefined &&
                wpBlastSettings.userAccount.features.maxPages !== null &&
                wpBlastSettings.userAccount.features.maxPages !== undefined
            ) {
                wpBlastSettingsMaxPagesToBlast = wpBlastSettings.userAccount.features.maxPages;
            }
            if (
                wpBlastSettings.userAccount.rights !== null &&
                wpBlastSettings.userAccount.rights !== undefined &&
                wpBlastSettings.userAccount.rights.hasGuestPlan !== null &&
                wpBlastSettings.userAccount.rights.hasGuestPlan !== undefined
            ) {
                wpBlastSettingsUserHasGuestPlan = wpBlastSettings.userAccount.rights.hasGuestPlan;
            }
        }

        // Add listeners for dashboard and cache section
        jQuery(document).ready(function ($) {
            jQuery('.toggleMessageCacheStatus').on('click', function () {
                jQuery('.wpblast-progress-explanation .message').toggle();
                jQuery('.wpblast-progress-explanation .action').toggleClass('dashicons-arrow-right-alt2');
                jQuery('.wpblast-progress-explanation .action').toggleClass('dashicons-arrow-down-alt2');
            });

            jQuery('.wpblast-register-form-open').on('click', function () {
                toggleRegisterForm();
            });

            jQuery('.wpblast-register-form-close').on('click', function () {
                toggleRegisterForm();
            });

            jQuery('.wpblast-registration-form-backdrop').on('click', function () {
                toggleRegisterForm();
            });

            jQuery('.wpblast-show-password').on('click', function (e) {
                e.preventDefault();
                if (this.textContent === 'Show') {
                    jQuery('.wpblast-register-account-password').get(0).type = 'text';
                    this.textContent = 'Hide';
                } else {
                    jQuery('.wpblast-register-account-password').get(0).type = 'password';
                    this.textContent = 'Show';
                }
            });

            jQuery('.wpblast-register-account-button').on('click', function (e) {
                const isEmailValid = (email) => {
                    return String(email)
                        .toLowerCase()
                        .match(
                            /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
                        );
                };

                let params = {
                    firstname: jQuery('.wpblast-register-account-firstname').val(),
                    lastname: jQuery('.wpblast-register-account-lastname').val(),
                    email: jQuery('.wpblast-register-account-email').val(),
                    password: jQuery('.wpblast-register-account-password').val(),
                    optin: jQuery('.wpblast-register-account-optin').is(':checked'),
                };

                if (
                    params.email.trim() === '' ||
                    params.password.trim() === '' ||
                    params.firstname.trim() === '' ||
                    params.lastname.trim() === ''
                ) {
                    e.preventDefault();
                    wpBlastTreatError('Please fill in all fields.', 'registerForm');
                    return;
                }

                if (!isEmailValid(params.email)) {
                    e.preventDefault();
                    wpBlastTreatError('Please enter a valid email.', 'registerForm');
                    return;
                }

                if (
                    wpBlastSettings !== undefined &&
                    wpBlastSettings.registrationForm !== undefined &&
                    wpBlastSettings.registrationForm.url !== undefined
                ) {
                    if (typeof URLSearchParams !== 'undefined') {
                        try {
                            this.href =
                                wpBlastSettings.registrationForm.url + '&' + new URLSearchParams(params).toString();
                        } catch (e) {
                            // in case of incompatibility or error, redirect to register form
                            this.href = wpBlastSettings.registrationForm.url;
                        }
                    } else {
                        // in case of incompatibility or error, redirect to register form
                        this.href = wpBlastSettings.registrationForm.url;
                    }
                }
            });

            jQuery('.wpblast-details-table-actions .wpblast-list-previous').on('click', function (e) {
                e.preventDefault();
                wpBlastSitemapOffset -= wpBlastItemPerPage;
                wpBlastSitemapLimit -= wpBlastItemPerPage;
                wpBlastCheckForPagination();
            });
            jQuery('.wpblast-details-table-actions .wpblast-list-next').on('click', function (e) {
                e.preventDefault();
                wpBlastSitemapOffset += wpBlastItemPerPage;
                wpBlastSitemapLimit += wpBlastItemPerPage;
                wpBlastCheckForPagination();
            });

            jQuery('.wpblast-list-save-selected-links').on('click', function (e) {
                e.preventDefault();
                // add original url
                var toSendUrls = [];
                var currentSelectedPages = getCurrentSelectedPages();
                for (var i = 0; i < currentSelectedPages.length; i++) {
                    if (Array.isArray(currentSelectedPages[i].originalUrls)) {
                        for (var j = 0; j < currentSelectedPages[i].originalUrls.length; j++) {
                            if (toSendUrls.indexOf(currentSelectedPages[i].originalUrls[j]) === -1)
                                toSendUrls.push(currentSelectedPages[i].originalUrls[j]);
                        }
                    }
                }
                wpBlastUpdateActivePages(toSendUrls);
            });
        });

        //Update Tab dashboard
        function updateWpBlastLoadingStep() {
            if (wpBlastSitemap.length === 0) {
                wpBlastLoadingStep = 1;
            } else if (wpBlastSitemap.length > 0) {
                if (wpBlastHomeItem.scores && wpBlastHomeItem.scores.scores) {
                    if (isLoadingSimulation(wpBlastHomeItem.scores.scores, 'raw', 'mobile')) {
                        wpBlastLoadingStep = 3;
                    } else if (isLoadingSimulation(wpBlastHomeItem.scores.scores, 'raw', 'desktop')) {
                        wpBlastLoadingStep = 4;
                    } else if (isLoadingSimulation(wpBlastHomeItem.scores.scores, 'blast', 'mobile')) {
                        wpBlastLoadingStep = 5;
                    } else if (isLoadingSimulation(wpBlastHomeItem.scores.scores, 'blast', 'desktop')) {
                        wpBlastLoadingStep = 6;
                    } else if (
                        wpBlastHomeItem.scores.scores &&
                        wpBlastHomeItem.scores.scores.raw &&
                        wpBlastHomeItem.scores.scores.blast &&
                        wpBlastHomeItem.scores.scores.raw.mobile &&
                        wpBlastHomeItem.scores.scores.raw.desktop &&
                        wpBlastHomeItem.scores.scores.blast.mobile &&
                        wpBlastHomeItem.scores.scores.blast.desktop &&
                        wpBlastHomeItem.scores.scores.raw.mobile.score !== undefined &&
                        wpBlastHomeItem.scores.scores.raw.desktop.score !== undefined &&
                        wpBlastHomeItem.scores.scores.blast.mobile.score !== undefined &&
                        wpBlastHomeItem.scores.scores.blast.desktop.score !== undefined
                    ) {
                        wpBlastLoadingStep = 7; // finish step
                    } else {
                        wpBlastLoadingStep = 2; // wait for loading of simulations
                    }
                } else {
                    wpBlastLoadingStep = 2;
                }
            }
            applyLoadingStep();
        }

        function applyLoadingStep() {
            if (typeof wpBlastSettings !== 'undefined' && wpBlastSettings.isLocalMode === true) {
                jQuery('.wpblast-introduction-dashboard-localhost').removeClass('hidden');
                jQuery('.wpblast-introduction-dashboard-image').removeClass('hidden');
                jQuery('.wpblast-introduction-dashboard-sitemap').addClass('hidden');
                jQuery('.wpblast-dashboard-sitemap-loading').addClass('hidden');
            } else if (wpBlastLoadingStep <= 6) {
                jQuery('.wpblast-dashboard-sitemap-loading').removeClass('hidden');
                jQuery('.wpblast-introduction-dashboard-image').removeClass('hidden');
                jQuery('.wpblast-introduction-dashboard-sitemap').addClass('hidden');
                for (let i = 1; i <= 6; i++) {
                    if (i < wpBlastLoadingStep) {
                        jQuery('.wpblast-loading-step-' + i + ' > .wpblast-loading-step-flash').html(
                            '<img src="' + pluginUrl + '/img/flash-green.svg" alt="Flash" width="15" />',
                        );
                    } else if (i === wpBlastLoadingStep) {
                        jQuery('.wpblast-loading-step-' + wpBlastLoadingStep + ' .wpblast-loading-step-flash').html(
                            '<div class="loading-score"></div>',
                        );
                    } else if (i > wpBlastLoadingStep) {
                        jQuery('.wpblast-loading-step-' + i + ' > .wpblast-loading-step-flash').html(
                            '<img src="' + pluginUrl + '/img/flash.svg" alt="Flash" width="15" />',
                        );
                    }
                }
            } else if (wpBlastLoadingStep === 7) {
                // finish loading
                jQuery('.wpblast-dashboard-sitemap-loading').addClass('hidden');
                jQuery('.wpblast-introduction-dashboard-sitemap').removeClass('hidden');
                jQuery('.wpblast-introduction-dashboard-image').removeClass('hidden');
                // update scoring of homepage
                if (
                    wpBlastHomeItem.scores.scores.raw &&
                    wpBlastHomeItem.scores.scores.raw.mobile &&
                    wpBlastHomeItem.scores.scores.raw.mobile.score !== undefined
                ) {
                    jQuery('.wpblast-introduction-dashboard-score-mobile-before').html(
                        !isNaN(parseFloat(wpBlastHomeItem.scores.scores.raw.mobile.score))
                            ? Math.round(parseFloat(wpBlastHomeItem.scores.scores.raw.mobile.score) * 100)
                            : '-',
                    );
                }
                if (
                    wpBlastHomeItem.scores.scores.blast &&
                    wpBlastHomeItem.scores.scores.blast.mobile &&
                    wpBlastHomeItem.scores.scores.blast.mobile.score !== undefined
                ) {
                    jQuery('.wpblast-introduction-dashboard-score-mobile-after').html(
                        !isNaN(parseFloat(wpBlastHomeItem.scores.scores.blast.mobile.score))
                            ? Math.round(parseFloat(wpBlastHomeItem.scores.scores.blast.mobile.score) * 100)
                            : '-',
                    );
                }
                if (
                    wpBlastHomeItem.scores.scores.raw &&
                    wpBlastHomeItem.scores.scores.raw.desktop &&
                    wpBlastHomeItem.scores.scores.raw.desktop.score !== undefined
                ) {
                    jQuery('.wpblast-introduction-dashboard-score-desktop-before').html(
                        !isNaN(parseFloat(wpBlastHomeItem.scores.scores.raw.desktop.score))
                            ? Math.round(parseFloat(wpBlastHomeItem.scores.scores.raw.desktop.score) * 100)
                            : '-',
                    );
                }
                if (
                    wpBlastHomeItem.scores.scores.blast &&
                    wpBlastHomeItem.scores.scores.blast.desktop &&
                    wpBlastHomeItem.scores.scores.blast.desktop.score !== undefined
                ) {
                    jQuery('.wpblast-introduction-dashboard-score-desktop-after').html(
                        !isNaN(parseFloat(wpBlastHomeItem.scores.scores.blast.desktop.score))
                            ? Math.round(parseFloat(wpBlastHomeItem.scores.scores.blast.desktop.score) * 100)
                            : '-',
                    );
                }
                if (
                    wpBlastHomeItem.scores.scores.raw &&
                    wpBlastHomeItem.scores.scores.raw.mobile &&
                    wpBlastHomeItem.scores.scores.raw.mobile.score !== undefined &&
                    wpBlastHomeItem.scores.scores.blast &&
                    wpBlastHomeItem.scores.scores.blast.mobile &&
                    wpBlastHomeItem.scores.scores.blast.mobile.score !== undefined
                ) {
                    var rawScore = parseFloat(wpBlastHomeItem.scores.scores.raw.mobile.score);
                    var blastScore = parseFloat(wpBlastHomeItem.scores.scores.blast.mobile.score);
                    if (
                        !isNaN(rawScore) &&
                        !isNaN(blastScore) &&
                        blastScore > rawScore &&
                        blastScore / rawScore > 1 &&
                        blastScore / rawScore < 2
                    ) {
                        jQuery('.wpblast-introduction-dashboard-score-multiplication-mobile').html(
                            '+' + Math.floor(((blastScore - rawScore) / rawScore) * 100) + '%',
                        );
                    } else if (
                        !isNaN(rawScore) &&
                        !isNaN(blastScore) &&
                        blastScore > rawScore &&
                        blastScore / rawScore >= 2
                    ) {
                        jQuery('.wpblast-introduction-dashboard-score-multiplication-mobile').html(
                            '×' + (blastScore / rawScore).toFixed(1),
                        );
                    }
                }
                if (
                    wpBlastHomeItem.scores.scores.raw &&
                    wpBlastHomeItem.scores.scores.raw.desktop &&
                    wpBlastHomeItem.scores.scores.raw.desktop.score !== undefined &&
                    wpBlastHomeItem.scores.scores.blast &&
                    wpBlastHomeItem.scores.scores.blast.desktop &&
                    wpBlastHomeItem.scores.scores.blast.desktop.score !== undefined
                ) {
                    var rawScore = parseFloat(wpBlastHomeItem.scores.scores.raw.desktop.score);
                    var blastScore = parseFloat(wpBlastHomeItem.scores.scores.blast.desktop.score);
                    if (
                        !isNaN(rawScore) &&
                        !isNaN(blastScore) &&
                        blastScore > rawScore &&
                        blastScore / rawScore > 1 &&
                        blastScore / rawScore < 2
                    ) {
                        jQuery('.wpblast-introduction-dashboard-score-multiplication-desktop').html(
                            '+' + Math.floor(((blastScore - rawScore) / rawScore) * 100) + '%',
                        );
                    } else if (
                        !isNaN(rawScore) &&
                        !isNaN(blastScore) &&
                        blastScore > rawScore &&
                        blastScore / rawScore >= 2
                    ) {
                        jQuery('.wpblast-introduction-dashboard-score-multiplication-desktop').html(
                            '×' + (blastScore / rawScore).toFixed(1),
                        );
                    }
                }
            }
        }

        var pluginUrl =
            wpBlastSettings !== undefined && wpBlastSettings.pluginUrl !== undefined ? wpBlastSettings.pluginUrl : '';

        // Add icon to Reassurance message and Maximum Pages reached div
        jQuery(document).ready(function ($) {
            $('.wpblast-details-reassurance-message-close').html(
                '<img src="' + pluginUrl + '/img/cross.svg" alt="Close icon" />',
            );
            $('.wpblast-details-reassurance-message-close').on('click', function (e) {
                e.preventDefault();
                jQuery('.wpblast-details-reassurance-message').removeClass('active');
            });
            $('.wpblast-details-maximum-pages-reached-close').on('click', function (e) {
                e.preventDefault();
                jQuery('.wpblast-details-maximum-pages-reached').addClass('close');
            });
        });

        function toggleRegisterForm() {
            jQuery('.wpblast-register-form').fadeToggle(400);
            jQuery('body').toggleClass('wpblast-registration-form-opened');
        }

        function wpBlastConvertHMS(value) {
            const sec = parseInt(value, 10); // convert value to number if it's string
            let hours = Math.floor(sec / 3600); // get hours
            let minutes = Math.floor((sec - hours * 3600) / 60); // get minutes
            let seconds = sec - hours * 3600 - minutes * 60; //  get seconds
            // add 0 if value < 10; Example: 2 => 02
            if (hours < 10) {
                hours = '0' + hours;
            }
            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            return hours + ':' + minutes + ':' + seconds; // Return is HH : MM : SS
        }
        function wpBlastCheckForPagination() {
            if (wpBlastSitemap.length <= wpBlastSitemapLimit) {
                jQuery('.wpblast-details-table-actions .wpblast-list-next').css('display', 'none');
            } else {
                jQuery('.wpblast-details-table-actions .wpblast-list-next').css('display', 'inline-block');
            }
            if (wpBlastSitemapOffset === 0) {
                jQuery('.wpblast-details-table-actions .wpblast-list-previous').css('display', 'none');
            } else {
                jQuery('.wpblast-details-table-actions .wpblast-list-previous').css('display', 'inline-block');
            }
            // add hash to update for the next getSitemap call
            var wpBlastSitemapItemScore;
            wpBlastSitemapItemsToUpdateScore = [];
            var wpBlastSliced = wpBlastSitemap.slice(wpBlastSitemapOffset, wpBlastSitemapLimit); // use slice version of sitemap to avoid triggering hundreds or thousands of update sql requests for something useless
            for (var i = 0; i < wpBlastSliced.length; i++) {
                wpBlastSitemapItemScore = wpBlastSliced[i].scores;
                var wpBlastHasEmptyScore =
                    wpBlastSitemapItemScore === '' ||
                    wpBlastSitemapItemScore === null ||
                    wpBlastSitemapItemScore === undefined;
                // In case of multiple hashes for the same url, only update the one with the lastRequest
                if (wpBlastSliced[i].details && wpBlastSliced[i].details.length > 0) {
                    var shouldUpdateScores = false;
                    for (var j = 0; j < wpBlastSliced[i].details.length; j++) {
                        for (var k = 0; k < types.length; k++) {
                            var type = types[k];
                            for (var l = 0; l < devices.length; l++) {
                                var device = devices[l];
                                if (
                                    wpBlastHasEmptyScore ||
                                    wpBlastSitemapItemScore['scores'][type] === undefined ||
                                    wpBlastSitemapItemScore['scores'][type][device] === undefined ||
                                    wpBlastSitemapItemScore['scores'][type][device]['requestUpdate'] === undefined
                                ) {
                                    wpBlastSitemapItemsToUpdateScore.push({
                                        hash: wpBlastSliced[i].details[j].hash,
                                        type: type,
                                        device: device,
                                        requestUpdate: true,
                                    });

                                    // Update is needed
                                    if (
                                        wpBlastSitemapUpdatedItems.indexOf(
                                            wpBlastSliced[i].details[j].hash + '-' + type + '-' + device,
                                        ) === -1
                                    ) {
                                        shouldUpdateScores = true;
                                    }
                                } else if (
                                    wpBlastSitemapUpdatedItems.indexOf(
                                        wpBlastSliced[i].details[j].hash + '-' + type + '-' + device,
                                    ) === -1
                                ) {
                                    wpBlastSitemapItemsToUpdateScore.push({
                                        hash: wpBlastSliced[i].details[j].hash,
                                        type: type,
                                        device: device,
                                        requestUpdate: false,
                                    });
                                    // Update is needed
                                    shouldUpdateScores = true;
                                } else if (
                                    wpBlastSitemapItemScore['scores'][type][device]['lastUpdate'] === undefined ||
                                    new Date(
                                        (
                                            wpBlastSitemapItemScore['scores'][type][device]['requestUpdate'] + ' UTC'
                                        ).replace(/-/g, '/'),
                                    ) >=
                                        new Date(
                                            (
                                                wpBlastSitemapItemScore['scores'][type][device]['lastUpdate'] + ' UTC'
                                            ).replace(/-/g, '/'),
                                        )
                                ) {
                                    wpBlastSitemapItemsToUpdateScore.push({
                                        hash: wpBlastSliced[i].details[j].hash,
                                        type: type,
                                        device: device,
                                        requestUpdate: false,
                                    });
                                }
                            }
                        }
                    }
                }
            }
            if (shouldUpdateScores && wpBlastSitemapItemsToUpdateScore.length > 0) {
                // speed up update of scores in case we didn't have updated it
                wpBlastGetSitemap();
            }
            wpBlastDisplayCacheTable();
            wpBlastDisplayDashboardTable();
            // Keep update state of opened details row
            wpBlastUpdateDetailsVisible();
            // Keep update state of checked row
            wpBlastUpdateChecked();
            // Add listeners for content of the tables
            wpBlastAddTableListeners();
            // Update dashboard message loading step
            updateWpBlastLoadingStep();
        }
        function wpBlastUpdateDetailsVisible() {
            // Reset every state
            jQuery('.wpblast-details .wpblast-details-table tr.wpblast-item').removeClass('visible');
            jQuery('.wpblast-details .wpblast-details-table tr.wpblast-item .detail-action a').addClass(
                'dashicons-arrow-right-alt2',
            );
            jQuery('.wpblast-details .wpblast-details-table tr.wpblast-item .detail-action a').removeClass(
                'dashicons-arrow-down-alt2',
            );
            jQuery(
                '.wpblast-details .wpblast-details-table tr.wpblast-item .scores .score-group .score-result',
            ).removeClass('small-score');
            // Update state with some visible
            for (var i = 0; i < wpBlastDetails.length; i++) {
                jQuery('.wpblast-details .wpblast-details-table tr.wpblast-item-' + wpBlastDetails[i]).addClass(
                    'visible',
                );
                jQuery(
                    '.wpblast-details .wpblast-details-table tr.wpblast-item-' +
                        wpBlastDetails[i] +
                        ' .detail-action a',
                ).removeClass('dashicons-arrow-right-alt2');
                jQuery(
                    '.wpblast-details .wpblast-details-table tr.wpblast-item-' +
                        wpBlastDetails[i] +
                        ' .detail-action a',
                ).addClass('dashicons-arrow-down-alt2');
                jQuery(
                    '.wpblast-details .wpblast-details-table tr.wpblast-item-' +
                        wpBlastDetails[i] +
                        ' .scores .score-group .score-result',
                ).addClass('small-score');
            }
        }

        function wpBlastIsRowActive(item) {
            if (item.isHomeUrl) {
                return true;
            } else if (wpBlastPagesSelected.hasOwnProperty(item.url)) {
                return wpBlastPagesSelected[item.url] === true;
            } else if (item.active === true) {
                return true;
            } else {
                return false;
            }
        }

        function wpBlastUpdateChecked() {
            var nbChecked = 0;
            var unselectedPages = [];
            for (var i = 0; i < wpBlastSitemap.length; i++) {
                // Manage which row should be checked
                if (wpBlastIsRowActive(wpBlastSitemap[i])) {
                    nbChecked++;
                    jQuery(".wpblast-list-checkbox-selected[data-checkbox='" + wpBlastSitemap[i].url + "']").prop(
                        'checked',
                        true,
                    );
                    // if row is active, no lock should be displayed
                    jQuery(".wpblast-item[data-url='" + wpBlastSitemap[i].url + "']").removeClass('inactive');
                    // add style to row checked
                    jQuery(".wpblast-item[data-url='" + wpBlastSitemap[i].url + "']").addClass('selected');
                } else {
                    jQuery(".wpblast-list-checkbox-selected[data-checkbox='" + wpBlastSitemap[i].url + "']").prop(
                        'checked',
                        false,
                    );
                    unselectedPages.push(wpBlastSitemap[i].url);
                    jQuery(".wpblast-item[data-url='" + wpBlastSitemap[i].url + "']").removeClass('selected');
                }
            }

            // Manage if select all checkbox should be checked
            if (unselectedPages.length === 0 || nbChecked >= wpBlastSettingsMaxPagesToBlast) {
                jQuery('.wpblast-list-checkbox-all').prop('checked', true);
            } else {
                jQuery('.wpblast-list-checkbox-all').prop('checked', false);
            }

            // Manage if row should be disabled
            for (var i = 0; i < unselectedPages.length; i++) {
                if (nbChecked < wpBlastSettingsMaxPagesToBlast) {
                    jQuery(".wpblast-item[data-url='" + unselectedPages[i] + "']").removeClass('inactive');
                } else {
                    jQuery(".wpblast-item[data-url='" + unselectedPages[i] + "']").addClass('inactive');
                }
            }

            // Update other selection dependent DOM element
            var currentSelectedPages = getCurrentSelectedPages();

            // Add number of remaining unoptimized pages
            if (wpBlastSitemap.length > 0 && currentSelectedPages.length > 0) {
                if (wpBlastSitemap.length - currentSelectedPages.length !== 0) {
                    jQuery('.wpblast-remaining-pages-unoptimized-container').show();
                    jQuery('.wpblast-remaining-pages-unoptimized').html(
                        wpBlastSitemap.length - currentSelectedPages.length,
                    );
                } else {
                    // hide and reset element
                    jQuery('.wpblast-remaining-pages-unoptimized-container').hide();
                    jQuery('.wpblast-remaining-pages-unoptimized').html('-');
                }
            } else {
                jQuery('.wpblast-remaining-pages-unoptimized-container').show();
                jQuery('.wpblast-remaining-pages-unoptimized').html('-');
            }

            jQuery('.wpblast-list-checkbox-all-text').html(
                Math.min(currentSelectedPages.length, wpBlastSettingsMaxPagesToBlast) +
                    '/' +
                    wpBlastSitemap.length +
                    '\n(max ' +
                    wpBlastSettingsMaxPagesToBlast +
                    ')',
            );

            if (wpBlastSitemap.length === 0 || wpBlastSettingsUserHasGuestPlan) {
                jQuery('.wpblast-list-save-selected-links').addClass('hidden');
                jQuery('.wpblast-details-table-page-speed').addClass('hidden');
            } else {
                jQuery('.wpblast-list-save-selected-links').removeClass('hidden');
                jQuery('.wpblast-details-table-page-speed').removeClass('hidden');
            }

            //Manage show message when the maximum of pages checked
            if (
                nbChecked >= wpBlastSettingsMaxPagesToBlast &&
                currentSelectedPages.length > 0 &&
                unselectedPages.length > 0
            ) {
                var hasNewSelection = false;
                for (var prop in wpBlastPagesSelected) {
                    if (wpBlastPagesSelected.hasOwnProperty(prop) && wpBlastPagesSelected[prop] === true) {
                        hasNewSelection = true;
                    }
                }
                if (currentSelectedPages.length < wpBlastItemPerPage + wpBlastSitemapOffset - 3) {
                    if (!hasNewSelection) {
                        jQuery('.wpblast-details-maximum-pages-reached').removeClass('hidden');
                        if (
                            currentSelectedPages.length > wpBlastSitemapOffset &&
                            currentSelectedPages.length < wpBlastSitemapOffset + wpBlastItemPerPage
                        ) {
                            jQuery('.wpblast-details-maximum-pages-reached').css(
                                'bottom',
                                (wpBlastItemPerPage - currentSelectedPages.length) * 3.5 + '%',
                            );
                        } else if (wpBlastSitemap.length < wpBlastSitemapLimit) {
                            if (wpBlastSitemap.length - wpBlastSitemapLimit <= 3) {
                                jQuery('.wpblast-details-maximum-pages-reached').addClass('hidden');
                            } else {
                                jQuery('.wpblast-details-maximum-pages-reached').css(
                                    'bottom',
                                    (wpBlastSitemap.length - wpBlastSitemapOffset) * 3.5 + '%',
                                );
                            }
                        } else if (currentSelectedPages.length < wpBlastSitemapOffset) {
                            jQuery('.wpblast-details-maximum-pages-reached').css('bottom', '33%');
                        }
                    } else {
                        jQuery('.wpblast-details-maximum-pages-reached').addClass('hidden');
                    }
                }
            } else {
                jQuery('.wpblast-details-maximum-pages-reached').addClass('hidden');
            }
        }

        function wpBlastAddTableListeners() {
            jQuery('.toggle-detail').on('click', function (e) {
                // Toggle details row
                e.preventDefault();
                var index = jQuery(this).attr('data-item');
                if (wpBlastDetails.indexOf(index) === -1) wpBlastDetails.push(index);
                else wpBlastDetails.splice(wpBlastDetails.indexOf(index), 1);
                wpBlastUpdateDetailsVisible();
            });
            jQuery('.wpblast-resimulate').on('click', function (e) {
                e.preventDefault();
                var index = jQuery(this).attr('data-item');
                var wpBlastSliced = wpBlastSitemap.slice(wpBlastSitemapOffset, wpBlastSitemapLimit);
                for (var j = 0; j < wpBlastSliced[index].details.length; j++) {
                    for (var k = 0; k < types.length; k++) {
                        var type = types[k];
                        for (var l = 0; l < devices.length; l++) {
                            var device = devices[l];
                            wpBlastSitemapItemsToUpdateScore.push({
                                hash: wpBlastSliced[index].details[j].hash,
                                type: type,
                                device: device,
                                requestUpdate: true,
                            });
                        }
                    }
                }
                // Force call to update
                wpBlastGetSitemap();
            });
            jQuery('.wpblast-list-checkbox-all').on('click', function () {
                if (this.checked) {
                    var currentSelectedPages = getCurrentSelectedPages();
                    // add every selected page to current selection (manual and from active property)
                    for (var i = 0; i < currentSelectedPages.length; i++) {
                        wpBlastPagesSelected[currentSelectedPages[i]] = true;
                    }
                    var remainingPages = wpBlastSettingsMaxPagesToBlast - currentSelectedPages.length;
                    // limit check all feature to max pages allowed in plan
                    for (var i = 0; i < wpBlastSitemap.length; i++) {
                        // Add home page to selected page as it doesn't count in currentSelectedPages
                        if (wpBlastSitemap[i].isHomeUrl) {
                            wpBlastPagesSelected[wpBlastSitemap[i].url] = true;
                        }
                        if (remainingPages > 0) {
                            if (
                                !wpBlastPagesSelected.hasOwnProperty(wpBlastSitemap[i].url) ||
                                wpBlastPagesSelected[wpBlastSitemap[i].url] === false
                            ) {
                                wpBlastPagesSelected[wpBlastSitemap[i].url] = true;
                                remainingPages--;
                            }
                        } else {
                            break;
                        }
                    }
                } else {
                    for (var i = 0; i < wpBlastSitemap.length; i++) {
                        if (!wpBlastSitemap[i].isHomeUrl) {
                            wpBlastPagesSelected[wpBlastSitemap[i].url] = false;
                        } else {
                            // home url is always selected
                            wpBlastPagesSelected[wpBlastSitemap[i].url] = true;
                        }
                    }
                }
                wpBlastUpdateChecked();
            });
            jQuery('.wpblast-list-checkbox-selected').change(function (e) {
                e.preventDefault();
                if (this.checked) {
                    if (jQuery(this).attr('data-checkbox')) {
                        wpBlastPagesSelected[jQuery(this).attr('data-checkbox')] = true;
                    }
                } else {
                    wpBlastPagesSelected[jQuery(this).attr('data-checkbox')] = false;
                }
                wpBlastUpdateChecked();
            });
            jQuery('.wpblast-reassurance-message').on('click', function (e) {
                e.preventDefault();
                jQuery('.wpblast-details-reassurance-message').toggleClass('active');
            });
        }

        var isLoadingSimulation = function (scores, type, device) {
            return (
                scores &&
                scores[type] &&
                scores[type][device] &&
                scores[type][device].requestUpdate &&
                (!scores[type][device].lastUpdate ||
                    (scores[type][device].lastUpdate &&
                        new Date((scores[type][device].lastUpdate + ' UTC').replace(/-/g, '/')) <
                            new Date((scores[type][device].requestUpdate + ' UTC').replace(/-/g, '/'))))
            );
        };

        function getCurrentSelectedPages() {
            var currentSelectedPages = [];
            for (var i = 0; i < wpBlastSitemap.length; i++) {
                if (wpBlastIsRowActive(wpBlastSitemap[i])) {
                    currentSelectedPages.push(wpBlastSitemap[i]);
                }
            }
            return currentSelectedPages;
        }

        function wpBlastDisplayDashboardTable() {
            var tableContent = wpBlastSitemap
                .slice(wpBlastSitemapOffset, wpBlastSitemapLimit)
                .map(function (pageItem, index) {
                    var lastRequestToLocal = pageItem.lastRequest ? pageItem.lastRequest.toLocaleString() : '-';

                    function formatScore(score) {
                        return score !== null && score !== undefined && !isNaN(score)
                            ? Math.round(parseFloat(score) * 100)
                            : score === '-'
                            ? '-'
                            : '<div class="loading-score"></div>';
                    }

                    var scores =
                        pageItem.scores !== undefined &&
                        pageItem.scores !== null &&
                        pageItem.scores.scores !== undefined &&
                        pageItem.scores.scores !== null
                            ? pageItem.scores.scores
                            : null;

                    var getScoreHtml = function (scores, type, device) {
                        if (scores && scores[type] && scores[type][device]) {
                            if (
                                scores[type][device].score &&
                                scores[type][device].lastUpdate &&
                                scores[type][device].requestUpdate &&
                                new Date((scores[type][device].lastUpdate + ' UTC').replace(/-/g, '/')) >=
                                    new Date((scores[type][device].requestUpdate + ' UTC').replace(/-/g, '/'))
                            ) {
                                return formatScore(scores[type][device].score);
                            } else if (isLoadingSimulation(scores, type, device)) {
                                return formatScore(null);
                            } else {
                                return formatScore('-');
                            }
                        } else {
                            return formatScore('-');
                        }
                    };

                    var getScore = function (scores, type, device) {
                        if (scores && scores[type] && scores[type][device]) {
                            if (
                                scores[type][device].score &&
                                scores[type][device].lastUpdate &&
                                scores[type][device].requestUpdate &&
                                new Date((scores[type][device].lastUpdate + ' UTC').replace(/-/g, '/')) >=
                                    new Date((scores[type][device].requestUpdate + ' UTC').replace(/-/g, '/'))
                            ) {
                                return !isNaN(scores[type][device].score)
                                    ? Math.round(parseFloat(scores[type][device].score) * 100)
                                    : null;
                            } else if (isLoadingSimulation(scores, type, device)) {
                                return null;
                            } else {
                                return null;
                            }
                        } else {
                            return null;
                        }
                    };

                    var rawScoresDesktopDetails = getScoreHtml(scores, 'raw', 'desktop');
                    var rawScoresMobileDetails = getScoreHtml(scores, 'raw', 'mobile');
                    var blastScoresDesktopDetails = getScoreHtml(scores, 'blast', 'desktop');
                    var blastScoresMobileDetails = getScoreHtml(scores, 'blast', 'mobile');

                    if (pageItem.details.length > 1) {
                        var urlDetails = '<div class="details">';
                        var nbRequestDetails = '<div class="details">';
                        var lastRequestDetails = '<div class="details">';
                        for (var i = 0; i < pageItem.details.length; i++) {
                            var detailItem = pageItem.details[i];
                            if (detailItem['hashVariables']) {
                                var hashVariables = JSON.parse(detailItem['hashVariables']);
                                if (
                                    hashVariables &&
                                    hashVariables['posts'] &&
                                    Array.isArray(hashVariables['posts']) &&
                                    hashVariables['posts'].length > 0 &&
                                    hashVariables['posts'][0]['date']
                                ) {
                                    urlDetails +=
                                        new Date(
                                            (hashVariables['posts'][0]['date'] + ' UTC').replace(/-/g, '/'),
                                        ).toLocaleString() + '<br/>';
                                } else {
                                    urlDetails += detailItem['hash'] + '<br/>';
                                }
                            } else {
                                urlDetails += detailItem['hash'] + '<br/>';
                            }
                            nbRequestDetails +=
                                detailItem['nbRequest'] !== null && detailItem['nbRequest'] !== undefined
                                    ? detailItem['nbRequest'] + '<br/>'
                                    : '-<br/>';
                            lastRequestDetails += detailItem['lastRequest']
                                ? new Date((detailItem['lastRequest'] + ' UTC').replace(/-/g, '/')).toLocaleString() +
                                  '<br/>'
                                : '-<br/>';
                        }
                        urlDetails += '</div>';
                        nbRequestDetails += '</div>';
                        lastRequestDetails += '</div>';
                        return (
                            '<tr class="wpblast-item wpblast-item-' +
                            index +
                            '" data-url="' +
                            pageItem.url +
                            '"><td class="detail-action">' +
                            (pageItem.url
                                ? '<a href="#" class="dashicons-before dashicons-arrow-right-alt2 toggle-detail" data-item="' +
                                  index +
                                  '"></a>'
                                : '') +
                            '</td><td class="detail-icon">' +
                            '<div class="icon-inactive"><img src="' +
                            pluginUrl +
                            '/img/lock.svg" alt="Lock icon" /></div>' +
                            '<div class="icon-active"><input type="checkbox" class="wpblast-list-checkbox-selected" data-checkbox="' +
                            pageItem.url +
                            '" ' +
                            (pageItem.isHomeUrl ? ' disabled' : '') +
                            ' /></div>' +
                            '</td><td>' +
                            (pageItem.url
                                ? "<a href='" +
                                  pageItem.url +
                                  "' target='_blank'>" +
                                  wpBlastHtmlEntities(pageItem.url) +
                                  '</a>' +
                                  urlDetails
                                : '') +
                            '</td><td>' +
                            (pageItem.nbRequest !== null && pageItem.nbRequest !== undefined
                                ? pageItem.nbRequest
                                : '') +
                            nbRequestDetails +
                            '</td><td>' +
                            lastRequestToLocal +
                            lastRequestDetails +
                            '</td><td class="scores">' +
                            '<div class="score-group"><div class="score-result raw-score">' +
                            rawScoresMobileDetails +
                            '</div><p class="device-label">Mobile</p></div>' +
                            '</td><td class="scores"><div class="score-group"><div class="score-result raw-score">' +
                            rawScoresDesktopDetails +
                            '</div><p class="device-label">Desktop</p></div>' +
                            '</td><td class="scores"><div class="score-group"><div class="score-result blast-score">' +
                            blastScoresMobileDetails +
                            '</div><p class="device-label">Mobile</p></div>' +
                            '</td><td class="scores"><div class="score-group"><div class="score-result blast-score">' +
                            blastScoresDesktopDetails +
                            '</div><p class="device-label">Desktop</p></div>' +
                            '</td><td>' +
                            (!(
                                isLoadingSimulation(scores, 'raw', 'mobile') ||
                                isLoadingSimulation(scores, 'raw', 'desktop') ||
                                isLoadingSimulation(scores, 'blast', 'mobile') ||
                                isLoadingSimulation(scores, 'blast', 'desktop')
                            )
                                ? '<a class="wpblast-resimulate dashicons dashicons-update-alt" data-item="' +
                                  index +
                                  '"></a>'
                                : '') +
                            '</td><td class="detail-reassurance">' +
                            (!(
                                isLoadingSimulation(scores, 'raw', 'mobile') ||
                                isLoadingSimulation(scores, 'raw', 'desktop') ||
                                isLoadingSimulation(scores, 'blast', 'mobile') ||
                                isLoadingSimulation(scores, 'blast', 'desktop')
                            ) &&
                            ((getScore(scores, 'blast', 'desktop') &&
                                getScore(scores, 'raw', 'desktop') &&
                                getScore(scores, 'blast', 'desktop') < 80 &&
                                getScore(scores, 'blast', 'desktop') - getScore(scores, 'raw', 'desktop') <= 15) ||
                                (getScore(scores, 'blast', 'mobile') &&
                                    getScore(scores, 'raw', 'mobile') &&
                                    getScore(scores, 'blast', 'mobile') < 80 &&
                                    getScore(scores, 'blast', 'mobile') - getScore(scores, 'raw', 'mobile') <= 15))
                                ? '<a class="wpblast-reassurance-message"><img src="' +
                                  pluginUrl +
                                  '/img/warning.svg" alt="Warning icon" /></a>'
                                : '') +
                            '</td></tr>'
                        );
                    } else {
                        return (
                            '<tr class="wpblast-item wpblast-item-' +
                            index +
                            '" data-url="' +
                            pageItem.url +
                            '"><td class="detail-action"></td><td class="detail-icon">' +
                            '<div class="icon-inactive"><img src="' +
                            pluginUrl +
                            '/img/lock.svg" alt="Lock icon" /></div>' +
                            '<div class="icon-active"><input type="checkbox" class="wpblast-list-checkbox-selected" data-checkbox="' +
                            pageItem.url +
                            '" ' +
                            (pageItem.isHomeUrl ? ' disabled' : '') +
                            ' /></div>' +
                            '</td><td>' +
                            (pageItem.url
                                ? "<a href='" +
                                  pageItem.url +
                                  "' target='_blank'>" +
                                  wpBlastHtmlEntities(pageItem.url) +
                                  '</a>'
                                : '') +
                            '</td><td>' +
                            (pageItem.nbRequest !== null && pageItem.nbRequest !== undefined
                                ? pageItem.nbRequest
                                : '') +
                            '</td><td>' +
                            lastRequestToLocal +
                            '</td><td style="width: 10px;" class="detail-score detail-score-raw"><div class="score-group"><div class="score-result raw-score">' +
                            rawScoresMobileDetails +
                            '</div><p class="device-label">Mobile</p></div>' +
                            '</td><td style="width: 10px;"  class="detail-score detail-score-raw"><div class="score-group"><div class="score-result raw-score">' +
                            rawScoresDesktopDetails +
                            '</div><p class="device-label">Desktop</p></div>' +
                            '</td><td style="width: 10px;"  class="detail-score detail-score-blast"><div class="score-group"><div class="score-result blast-score">' +
                            blastScoresMobileDetails +
                            '</div><p class="device-label">Mobile</p></div>' +
                            '</td><td style="width: 10px;"  class="detail-score detail-score-blast"><div class="score-group"><div class="score-result blast-score">' +
                            blastScoresDesktopDetails +
                            '</div><p class="device-label">Desktop</p></div>' +
                            '</td><td class="detail-resimulate">' +
                            (!(
                                isLoadingSimulation(scores, 'raw', 'mobile') ||
                                isLoadingSimulation(scores, 'raw', 'desktop') ||
                                isLoadingSimulation(scores, 'blast', 'mobile') ||
                                isLoadingSimulation(scores, 'blast', 'desktop')
                            )
                                ? '<a class="wpblast-resimulate dashicons dashicons-update-alt" data-item="' +
                                  index +
                                  '"></a>'
                                : '') +
                            '</td><td class="detail-reassurance">' +
                            (!(
                                isLoadingSimulation(scores, 'raw', 'mobile') ||
                                isLoadingSimulation(scores, 'raw', 'desktop') ||
                                isLoadingSimulation(scores, 'blast', 'mobile') ||
                                isLoadingSimulation(scores, 'blast', 'desktop')
                            ) &&
                            ((getScore(scores, 'blast', 'desktop') &&
                                getScore(scores, 'raw', 'desktop') &&
                                getScore(scores, 'blast', 'desktop') < 80 &&
                                getScore(scores, 'blast', 'desktop') - getScore(scores, 'raw', 'desktop') <= 15) ||
                                (getScore(scores, 'blast', 'mobile') &&
                                    getScore(scores, 'raw', 'mobile') &&
                                    getScore(scores, 'blast', 'mobile') < 80 &&
                                    getScore(scores, 'blast', 'mobile') - getScore(scores, 'raw', 'mobile') <= 15))
                                ? '<a class="wpblast-reassurance-message"><img src="' +
                                  pluginUrl +
                                  '/img/warning.svg" alt="Warning icon" /></a>'
                                : '') +
                            '</td></tr>'
                        );
                    }
                });
            if (wpBlastSitemap.length > 0) {
                jQuery('.wpblast-details-table.dashboard').html(
                    '<table class="wp-list-table widefat striped table-view-list"><thead><tr><th class="detail-action"></th><th class="detail-icon"><input type="checkbox" class="wpblast-list-checkbox-all" name="checkbox-all" value="All"' +
                        (wpBlastSettingsUserHasGuestPlan ? ' disabled' : '') +
                        '><p class="wpblast-list-checkbox-all-text"></p></th><th>URL</th><th>Number of crawler requests</th><th>Last crawler request</th><th class="center-heading" colspan="2" style="max-width: 50px;"><span style="display: block;margin-bottom: 10px;margin-top: 5px;">Native</span><span style="display: block;">Performance score</span></th><th class="center-heading" colspan="2" style="max-width: 50px;"><a target="_blank" class="table-logo-container" href="' +
                        wpBlastSettings.wpblastUrl +
                        '"><img src="' +
                        pluginUrl +
                        '/img/icon.svg" alt="Logo" width="30" /><span class="table-logo">WP BLAST</span></a><span style="display: block;margin-top: 9px;">Performance score</span></th><th style="max-width: 20px;"></th><th style="max-width: 20px;"></th></tr></thead><tbody>' +
                        tableContent.join('') +
                        '</tbody></table>',
                );
            } else jQuery('.wpblast-details-table.dashboard').html('');
        }

        function wpBlastDisplayCacheTable() {
            // Update sitemap details
            // Build the list
            var tableContent = wpBlastSitemap
                .slice(wpBlastSitemapOffset, wpBlastSitemapLimit)
                .map(function (cacheItem, index) {
                    var lastRequestToLocal = cacheItem.lastRequest ? cacheItem.lastRequest.toLocaleString() : '-';
                    var lastGenToLocal = cacheItem.lastGen ? cacheItem.lastGen.toLocaleString() : '-';
                    var expireIn = '';
                    if (cacheItem.cacheExpiration !== undefined && cacheItem.cacheExpiration !== null) {
                        var cacheExpiration = parseInt(cacheItem.cacheExpiration);
                        if (!isNaN(cacheExpiration)) {
                            if (cacheExpiration === 0) {
                                expireIn = 'Expired';
                            } else {
                                var timeLeftSeconds = cacheExpiration - Math.round(Date.now() / 1000);
                                if (timeLeftSeconds > 0) expireIn = wpBlastConvertHMS(timeLeftSeconds);
                                else expireIn = 'Expired';
                            }
                        }
                    }
                    if (cacheItem.details.length > 1) {
                        var urlDetails = '<div class="details">';
                        var nbRequestDetails = '<div class="details">';
                        var lastRequestDetails = '<div class="details">';
                        var lastGenDetails = '<div class="details">';
                        var cacheExpirationDetails = '<div class="details">';
                        for (var i = 0; i < cacheItem.details.length; i++) {
                            var detailItem = cacheItem.details[i];
                            if (detailItem['hashVariables']) {
                                var hashVariables = JSON.parse(detailItem['hashVariables']);
                                if (
                                    hashVariables &&
                                    hashVariables['posts'] &&
                                    Array.isArray(hashVariables['posts']) &&
                                    hashVariables['posts'].length > 0 &&
                                    hashVariables['posts'][0]['date']
                                ) {
                                    urlDetails +=
                                        new Date(
                                            (hashVariables['posts'][0]['date'] + ' UTC').replace(/-/g, '/'),
                                        ).toLocaleString() + '<br/>';
                                } else {
                                    urlDetails += detailItem['hash'] + '<br/>';
                                }
                            } else {
                                urlDetails += detailItem['hash'] + '<br/>';
                            }
                            nbRequestDetails +=
                                detailItem['nbRequest'] !== null && detailItem['nbRequest'] !== undefined
                                    ? detailItem['nbRequest'] + '<br/>'
                                    : '-<br/>';
                            lastRequestDetails += detailItem['lastRequest']
                                ? new Date((detailItem['lastRequest'] + ' UTC').replace(/-/g, '/')).toLocaleString() +
                                  '<br/>'
                                : '-<br/>';
                            lastGenDetails += detailItem['lastGen']
                                ? new Date((detailItem['lastGen'] + ' UTC').replace(/-/g, '/')).toLocaleString() +
                                  '<br/>'
                                : '-<br/>';
                            var cacheExpirationDetail = parseInt(detailItem['cacheExpiration']);
                            if (!isNaN(cacheExpirationDetail)) {
                                if (cacheExpirationDetail === 0) {
                                    expireInDetails = 'Expired';
                                } else {
                                    var timeLeftSecondsDetails = cacheExpirationDetail - Math.round(Date.now() / 1000);
                                    if (timeLeftSecondsDetails > 0)
                                        expireInDetails = wpBlastConvertHMS(timeLeftSecondsDetails);
                                    else expireInDetails = 'Expired';
                                }
                            }
                            cacheExpirationDetails += expireInDetails + '<br/>';
                        }
                        urlDetails += '</div>';
                        nbRequestDetails += '</div>';
                        lastRequestDetails += '</div>';
                        lastGenDetails += '</div>';
                        cacheExpirationDetails += '</div>';
                        return (
                            '<tr class="wpblast-item wpblast-item-' +
                            index +
                            '" data-url="' +
                            cacheItem.url +
                            '"><td class="detail-action">' +
                            (cacheItem.url
                                ? '<a href="#" class="dashicons-before dashicons-arrow-right-alt2 toggle-detail" data-item="' +
                                  index +
                                  '"></a>'
                                : '') +
                            '</td><td class="detail-icon">' +
                            '<div class="icon-inactive"><img src="' +
                            pluginUrl +
                            '/img/lock.svg" alt="Lock icon" /></div>' +
                            '<div class="icon-active"><input type="checkbox" class="wpblast-list-checkbox-selected" data-checkbox="' +
                            cacheItem.url +
                            '" ' +
                            (cacheItem.isHomeUrl ? ' disabled' : '') +
                            ' /></div>' +
                            '</td><td>' +
                            (cacheItem.url
                                ? "<a href='" +
                                  cacheItem.url +
                                  "' target='_blank'>" +
                                  wpBlastHtmlEntities(cacheItem.url) +
                                  '</a>' +
                                  urlDetails
                                : '') +
                            '</td><td>' +
                            (cacheItem.nbRequest !== null && cacheItem.nbRequest !== undefined
                                ? cacheItem.nbRequest
                                : '') +
                            nbRequestDetails +
                            '</td><td>' +
                            lastRequestToLocal +
                            lastRequestDetails +
                            '</td><td>' +
                            lastGenToLocal +
                            lastGenDetails +
                            '</td><td>' +
                            expireIn +
                            cacheExpirationDetails +
                            '</td></tr>'
                        );
                    } else {
                        return (
                            '<tr class="wpblast-item wpblast-item-' +
                            index +
                            '" data-url="' +
                            cacheItem.url +
                            '"><td class="detail-action"></td><td class="detail-icon">' +
                            '<div class="icon-inactive"><img src="' +
                            pluginUrl +
                            '/img/lock.svg" alt="Lock icon" /></div>' +
                            '<div class="icon-active"><input type="checkbox" class="wpblast-list-checkbox-selected" data-checkbox="' +
                            cacheItem.url +
                            '" ' +
                            (cacheItem.isHomeUrl ? ' disabled' : '') +
                            ' /></div>' +
                            '</td><td>' +
                            (cacheItem.url
                                ? "<a href='" +
                                  cacheItem.url +
                                  "' target='_blank'>" +
                                  wpBlastHtmlEntities(cacheItem.url) +
                                  '</a>'
                                : '') +
                            '</td><td>' +
                            (cacheItem.nbRequest !== null && cacheItem.nbRequest !== undefined
                                ? cacheItem.nbRequest
                                : '') +
                            '</td><td>' +
                            lastRequestToLocal +
                            '</td><td>' +
                            lastGenToLocal +
                            '</td><td>' +
                            expireIn +
                            '</td></tr>'
                        );
                    }
                });
            if (wpBlastSitemap.length > 0) {
                jQuery('.wpblast-details-table.sitemap').html(
                    '<table class="wp-list-table widefat striped table-view-list"><thead><tr><th class="detail-action"></th><th class="detail-icon"><input type="checkbox" class="wpblast-list-checkbox-all" name="checkbox-all" value="All"' +
                        (wpBlastSettingsUserHasGuestPlan ? ' disabled' : '') +
                        '/><p class="wpblast-list-checkbox-all-text"></p></th><th>URL</th><th>Number of crawler requests</th><th>Last crawler request</th><th>Date of cache</th><th>Cache expire in</th></tr></thead><tbody>' +
                        tableContent.join('') +
                        '</tbody></table>',
                );
            } else {
                jQuery('.wpblast-details-table.sitemap').html('');
            }
        }

        function wpBlastGetSitemapData(wpBlastSitemapData) {
            // Format answer to build a table with a detailed view
            var wpBlastSitemapObject = {};
            var item;
            var itemUrl;
            var isHomeUrl = function (pageItem) {
                return !(
                    pageItem.url !== wpBlastSettings.userAccount.homeUrl &&
                    pageItem.url !== wpBlastSettings.userAccount.homeUrl + '/' &&
                    pageItem.url !== wpBlastSettings.userAccount.homeUrl + '/index.php' &&
                    pageItem.url !== wpBlastSettings.userAccount.homeUrl + '/index.html'
                );
            };
            for (var i = 0; i < wpBlastSitemapData.length; i++) {
                item = wpBlastSitemapData[i];
                if (item.hasOwnProperty('url')) {
                    // Sanitize url
                    if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                        try {
                            var sanitizeUrl = new URL(item.url);
                            sanitizeUrl.searchParams.delete('__wpblast_crawler');
                            itemUrl = sanitizeUrl.toString();
                        } catch (e) {
                            itemUrl = item.url;
                        }
                    } else if (i === 0) {
                        // display only one time the error in console
                        console.error('Your browser does not support URLSearchParams');
                        itemUrl = item.url;
                    } else {
                        itemUrl = item.url;
                    }
                    if (wpBlastSitemapObject.hasOwnProperty(itemUrl)) {
                        var lastGenItem = item.lastGen ? new Date((item.lastGen + ' UTC').replace(/-/g, '/')) : null;
                        var lastGenPrevious = wpBlastSitemapObject[itemUrl]['lastGen'];
                        var lastRequestItem = item.lastRequest
                            ? new Date((item.lastRequest + ' UTC').replace(/-/g, '/'))
                            : null;
                        var lastRequestPrevious = wpBlastSitemapObject[itemUrl]['lastRequest'];
                        var scoresItem = item.scores && item.scores !== '' ? JSON.parse(item.scores) : null;
                        var scoresPrevious = wpBlastSitemapObject[itemUrl]['scores'];
                        wpBlastSitemapObject[itemUrl]['cacheExpiration'] =
                            item.cacheExpiration &&
                            item.cacheExpiration > wpBlastSitemapObject[itemUrl]['cacheExpiration']
                                ? item.cacheExpiration
                                : wpBlastSitemapObject[itemUrl]['cacheExpiration'];
                        wpBlastSitemapObject[itemUrl]['lastGen'] =
                            lastGenItem !== null && lastGenPrevious === null
                                ? lastGenItem
                                : lastGenItem === null && lastGenPrevious !== null
                                ? lastGenPrevious
                                : lastGenItem === null && lastGenPrevious === null
                                ? null
                                : lastGenItem > lastGenPrevious
                                ? lastGenItem
                                : lastGenPrevious;
                        if (lastRequestItem !== null && lastRequestPrevious === null) {
                            wpBlastSitemapObject[itemUrl]['lastRequest'] = lastRequestItem;
                            wpBlastSitemapObject[itemUrl]['scores'] = scoresItem;
                        } else if (lastRequestItem === null && lastRequestPrevious !== null) {
                            wpBlastSitemapObject[itemUrl]['lastRequest'] = lastRequestPrevious;
                            wpBlastSitemapObject[itemUrl]['scores'] = scoresPrevious;
                        } else if (lastRequestItem === null && lastRequestPrevious === null) {
                            wpBlastSitemapObject[itemUrl]['lastRequest'] = null;
                            if (scoresItem !== null) wpBlastSitemapObject[itemUrl]['scores'] = scoresItem;
                            else if (scoresPrevious !== null) wpBlastSitemapObject[itemUrl]['scores'] = scoresPrevious;
                            else wpBlastSitemapObject[itemUrl]['scores'] = null;
                        } else if (lastRequestItem > lastRequestPrevious) {
                            wpBlastSitemapObject[itemUrl]['lastRequest'] = lastRequestItem;
                            wpBlastSitemapObject[itemUrl]['scores'] = scoresItem;
                        } else {
                            wpBlastSitemapObject[itemUrl]['lastRequest'] = lastRequestPrevious;
                            wpBlastSitemapObject[itemUrl]['scores'] = scoresPrevious;
                        }
                        wpBlastSitemapObject[itemUrl]['nbRequest'] = item.nbRequest
                            ? wpBlastSitemapObject[itemUrl]['nbRequest'] + parseInt(item.nbRequest)
                            : wpBlastSitemapObject[itemUrl]['nbRequest'];
                        wpBlastSitemapObject[itemUrl]['url'] = itemUrl;
                        wpBlastSitemapObject[itemUrl]['originalUrls'].push(item.url);
                        wpBlastSitemapObject[itemUrl]['details'].push(item);
                        wpBlastSitemapObject[itemUrl]['active'] =
                            wpBlastSitemapObject[itemUrl]['active'] || item.active === '1'; // consider a group active if at least one hash of the group is active
                        // isHomeUrl shouldn't be update as it only depends on the url and the url is part of the grouping
                    } else {
                        wpBlastSitemapObject[itemUrl] = {
                            cacheExpiration: item.cacheExpiration,
                            lastGen: item.lastGen ? new Date((item.lastGen + ' UTC').replace(/-/g, '/')) : null,
                            lastRequest: item.lastRequest
                                ? new Date((item.lastRequest + ' UTC').replace(/-/g, '/'))
                                : null,
                            nbRequest: parseInt(item.nbRequest),
                            scores: item.scores && item.scores !== '' ? JSON.parse(item.scores) : null,
                            url: itemUrl,
                            originalUrls: [item.url],
                            details: [item],
                            isHomeUrl: isHomeUrl(item),
                            active: item.active === '1' ? true : false,
                        };
                    }
                    // Save home url item for later use
                    if (isHomeUrl(item)) {
                        wpBlastHomeItem = wpBlastSitemapObject[itemUrl];
                    }
                }
            }
            // put home url at the start of the sitemap to have it always on top
            if (wpBlastHomeItem.url && wpBlastSitemapObject.hasOwnProperty(wpBlastHomeItem.url)) {
                delete wpBlastSitemapObject[wpBlastHomeItem.url];
                wpBlastSitemapObject = Object.assign({ [wpBlastHomeItem.url]: wpBlastHomeItem }, wpBlastSitemapObject);
            }
            wpBlastSitemap = Object.values(wpBlastSitemapObject);
            var wpblast_cached_items = wpBlastSitemap.filter(function (cacheItem) {
                var hasCache =
                    cacheItem.cacheExpiration !== undefined &&
                    cacheItem.cacheExpiration !== null &&
                    !isNaN(parseInt(cacheItem.cacheExpiration)) &&
                    Math.round(Date.now() / 1000) - parseInt(cacheItem.cacheExpiration) <= 0;
                return hasCache;
            });
            var wpblast_valid_items = wpBlastSitemap.filter(function (cacheItem) {
                var hasCache =
                    cacheItem.cacheExpiration !== undefined &&
                    cacheItem.cacheExpiration !== null &&
                    !isNaN(parseInt(cacheItem.cacheExpiration)) &&
                    Math.round(Date.now() / 1000) - parseInt(cacheItem.cacheExpiration) <= 0;
                return (
                    hasCache ||
                    (!hasCache &&
                        wpBlastIsRowActive(cacheItem) &&
                        cacheItem.nbRequest !== 0 &&
                        cacheItem.nbRequest !== '0' &&
                        cacheItem.nbRequest !== undefined &&
                        cacheItem.nbRequest !== null)
                );
            });
            var wpblast_cache_percent = wpblast_valid_items.length
                ? Math.min(Math.floor((wpblast_cached_items.length / wpblast_valid_items.length) * 100), 100)
                : '-';
            if (wpblast_cache_percent !== 100 || wpblast_cache_percent === '-') {
                jQuery('.wpblast-progress-explanation').show();
            } else {
                jQuery('.wpblast-progress-explanation').hide();
            }
            jQuery('.wpblast-progress-bar-text').html(
                wpblast_cache_percent + '% (' + wpblast_cached_items.length + '/' + wpblast_valid_items.length + ')',
            );
            jQuery('.wpblast-progress-bar div').css('width', wpblast_cache_percent + '%');
            wpBlastCheckForPagination();
        }

        function wpBlastGetSitemap() {
            // avoid a call in case a request is already loading. This is to prevent request from stacking
            if (!wpBlastIsLoadingSitemap) {
                // Get sitemap stored in website database
                var dataToSend = {};
                if (wpBlastSitemapItemsToUpdateScore.length > 0) {
                    dataToSend['items'] = wpBlastSitemapItemsToUpdateScore;
                    for (var i = 0; i < wpBlastSitemapItemsToUpdateScore.length; i++) {
                        // mark this hash has updated, this will prevent an update until next reload of the page
                        wpBlastSitemapUpdatedItems.push(
                            wpBlastSitemapItemsToUpdateScore[i].hash +
                                '-' +
                                wpBlastSitemapItemsToUpdateScore[i].type +
                                '-' +
                                wpBlastSitemapItemsToUpdateScore[i].device,
                        );
                    }
                }
                var restUrl = '/wp-json/wpblast/v1/getSitemap?wpblast_nonce=' + wpblast_nonce; // fallback rest url
                if (
                    typeof wpBlastSettings !== 'undefined' &&
                    wpBlastSettings.restUrls !== null &&
                    wpBlastSettings.restUrls !== undefined
                ) {
                    if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                        try {
                            var url = new URL(wpBlastSettings.restUrls.getSiteMap);
                            url.searchParams.append('wpblast_nonce', wpblast_nonce);
                            restUrl = url.toString();
                        } catch (e) {
                            // Fallback url
                            restUrl = wpBlastSettings.restUrls.getSiteMap + '?wpblast_nonce=' + wpblast_nonce;
                        }
                    } else {
                        // Fallback url
                        restUrl = wpBlastSettings.restUrls.getSiteMap + '?wpblast_nonce=' + wpblast_nonce;
                    }
                }
                wpBlastIsLoadingSitemap = true;
                jQuery.ajax({
                    url: restUrl,
                    headers: {
                        'X-WP-Nonce': wpblast_nonce,
                        'content-type': 'application/json',
                    },
                    method: 'POST',
                    data: JSON.stringify(dataToSend),
                    success: function (data) {
                        if (
                            data &&
                            data.response &&
                            data.response.sitemap !== undefined &&
                            data.response.data !== null
                        ) {
                            wpBlastGetSitemapData(data.response.sitemap);
                        }

                        // Should be update after possible update of wpBlastSitemap
                        if ((isGeneratingCache || wpBlastSitemap.length === 0) && wpBlastGetSitemapFrequency !== 1000) {
                            clearInterval(wpBlastGetSitemapInterval);
                            wpBlastGetSitemapFrequency = 1000;
                            if (window.location.search.indexOf('debug') === -1) {
                                wpBlastGetSitemapInterval = setInterval(wpBlastGetSitemap, wpBlastGetSitemapFrequency);
                            }
                        } else if (
                            !(isGeneratingCache || wpBlastSitemap.length === 0) &&
                            wpBlastGetSitemapFrequency !== 5000
                        ) {
                            clearInterval(wpBlastGetSitemapInterval);
                            wpBlastGetSitemapFrequency = 5000;
                            if (window.location.search.indexOf('debug') === -1) {
                                wpBlastGetSitemapInterval = setInterval(wpBlastGetSitemap, wpBlastGetSitemapFrequency);
                            }
                        }
                    },
                    complete: function () {
                        wpBlastIsLoadingSitemap = false;
                    },
                });
            }
        }

        function wpBlastUpdateActivePages(activeUrls = []) {
            // Get sitemap stored in website database
            var dataToSend = {
                activeUrls: activeUrls,
            };

            var restUrl = '/wp-json/wpblast/v1/updateActivePages?wpblast_nonce=' + wpblast_nonce; // fallback rest url
            if (
                typeof wpBlastSettings !== 'undefined' &&
                wpBlastSettings.restUrls !== null &&
                wpBlastSettings.restUrls !== undefined
            ) {
                if (typeof URLSearchParams !== 'undefined' && typeof URL !== 'undefined') {
                    try {
                        var url = new URL(wpBlastSettings.restUrls.updateActivePages);
                        url.searchParams.append('wpblast_nonce', wpblast_nonce);
                        restUrl = url.toString();
                    } catch (e) {
                        // Fallback url
                        restUrl = wpBlastSettings.restUrls.updateActivePages + '?wpblast_nonce=' + wpblast_nonce;
                    }
                } else {
                    // Fallback url
                    restUrl = wpBlastSettings.restUrls.updateActivePages + '?wpblast_nonce=' + wpblast_nonce;
                }
            }
            jQuery.ajax({
                url: restUrl,
                headers: {
                    'X-WP-Nonce': wpblast_nonce,
                    'content-type': 'application/json',
                },
                method: 'POST',
                data: JSON.stringify(dataToSend),
                success: function (data) {
                    if (data && data.response && data.response.sitemap !== undefined && data.response.data !== null) {
                        wpBlastGetSitemapData(data.response.sitemap);
                    }
                    // update new state of pages to wp-blast.com
                    updatePluginData();
                },
            });
        }
    } else {
        console.error('jQuery is necessary for wpblast admin');
    }
})();
