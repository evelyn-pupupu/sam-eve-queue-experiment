Qualtrics.SurveyEngine.addOnReady(function() {
    var that = this;

    this.hideNextButton();

    var iframe = this.getQuestionContainer().querySelector('iframe');
    console.log('iframe found:', iframe);

    if (!iframe) {
        console.error('Iframe not found within the question container.');
        that.clickNextButton();
        return;
    }

    window.addEventListener('message', function(event) {
        if (isInMobilePreview()) {
            console.log('Mobile preview detected. Skipping data-saving code.');
            that.clickNextButton();
            return;
        }

        if (event.data && event.data.type === 'RESPONSE_SUBMIT_TIMES') {
            var submitTimes = event.data.submitTimes;
            console.log('Received submitTimes from iframe:', submitTimes);

            Qualtrics.SurveyEngine.setEmbeddedData('submitTimes', JSON.stringify(submitTimes));

            that.clickNextButton();
        }
    });

    // Function to detect if running in the mobile preview
    function isInMobilePreview() {
        try {
            // Check if the current window is inside an iframe with id 'mobile-preview-view'
            return window.frameElement && window.frameElement.id === 'mobile-preview-view';
        } catch (e) {
            return false;
        }
    }

    setTimeout(function() {
        var message = {
            type: 'REQUEST_SUBMIT_TIMES'
        };

        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage(message, '*');
            console.log('Message sent to iframe:', message);
        } else {
            console.error('Iframe not accessible or contentWindow is null.');
            that.clickNextButton();
        }
    }, 60000); // 600000 = 10 minutes, 60000 = 1 minutes for test, 24000 = 24 secs for debug
});
