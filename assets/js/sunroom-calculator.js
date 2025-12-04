/**
 * THE SUNROOM EXPERTS - QUOTE CALCULATOR
 * WordPress Version - jQuery Compatible
 * 
 * FIXES APPLIED:
 * - Start with calculator screen (no landing duplication)
 * - State persistence when navigating between screens
 * - Improved inline error validation
 * - Timeline field support
 * - Better phone validation
 */

(function($) {
    'use strict';

    // Global state management
    let calculatorState = {
        currentScreen: 'calculator', // Changed from 'landing' to start on calculator
        selectedRoom: null,
        roomTypeName: '',
        dimensions: {
            wall1: 0,
            wall2: 0,
            wall3: 0,
            height: 8 // Standard height
        },
        calculatedPrice: {
            min: 0,
            max: 0,
            totalLinearFeet: 0,
            totalSquareFeet: 0
        },
        contactInfo: {
            fullName: '',
            email: '',
            phone: '',
            address: '',
            city: '',
            state: '',
            zip: '',
            projectDetails: '',
            timeline: ''
        }
    };

    // Room pricing configuration
const roomPricing = {
    screen: {
        name: 'Screen Only',
        minPerLinearFt: 100,
        maxPerLinearFt: 130
    },
    '3season': {
        name: '3-Season Eze Breeze',
        minPerLinearFt: 200,
        maxPerLinearFt: 300
    },
    glass: {
        name: 'Glass',
        minPerLinearFt: 600,
        maxPerLinearFt: 800
    }
};

    // ==============================================
    // SCREEN NAVIGATION WITH STATE PERSISTENCE
    // ==============================================

    function showScreen(screenId) {
        console.log('Showing screen:', screenId);
        
        // Save current form data before switching screens
        if (calculatorState.currentScreen === 'contact') {
            saveContactFormState();
        }
        
        // Hide all screens
        $('.screen').removeClass('active');
        
        // Show selected screen with slight delay for smooth transition
        setTimeout(function() {
            $('#screen-' + screenId).addClass('active');
            calculatorState.currentScreen = screenId;
            
            // Restore state when going back to calculator
            if (screenId === 'calculator') {
                restoreCalculatorState();
            }
            
            // Restore contact form when going back to contact
            if (screenId === 'contact') {
                restoreContactFormState();
            }
            
            // Scroll to top smoothly
            $('html, body').animate({ scrollTop: 0 }, 300);
        }, 100);
    }

    // ==============================================
    // STATE PERSISTENCE FUNCTIONS
    // ==============================================

    function restoreCalculatorState() {
        console.log('Restoring calculator state');
        
        // Restore room selection
        if (calculatorState.selectedRoom) {
            $('.room-card').each(function() {
                const $card = $(this);
                if ($card.data('room-type') === calculatorState.selectedRoom) {
                    $card.addClass('selected');
                } else {
                    $card.removeClass('selected');
                }
            });
            
            // Enable measurement section
            $('#measurement-section').removeClass('disabled');
            $('#wall1, #wall2, #wall3').prop('disabled', false);
        }
        
        // Restore wall measurements
        if (calculatorState.dimensions.wall1 > 0) {
            $('#wall1').val(calculatorState.dimensions.wall1);
        }
        if (calculatorState.dimensions.wall2 > 0) {
            $('#wall2').val(calculatorState.dimensions.wall2);
        }
        if (calculatorState.dimensions.wall3 > 0) {
            $('#wall3').val(calculatorState.dimensions.wall3);
        }
        
        // Recalculate and show price if we have data
        if (calculatorState.selectedRoom && calculatorState.calculatedPrice.min > 0) {
            updatePriceDisplay();
        }
    }

    function saveContactFormState() {
        calculatorState.contactInfo = {
            fullName: $('#fullName').val().trim(),
            email: $('#email').val().trim(),
            phone: $('#phone').val().trim(),
            address: $('#address').val().trim(),
            city: $('#city').val().trim(),
            state: $('#state').val().trim(),
            zip: $('#zip').val().trim(),
            projectDetails: $('#project-details').val().trim(),
            timeline: $('#timeline').val()
        };
        console.log('Contact form state saved');
    }

    function restoreContactFormState() {
        console.log('Restoring contact form state');
        
        $('#fullName').val(calculatorState.contactInfo.fullName);
        $('#email').val(calculatorState.contactInfo.email);
        $('#phone').val(calculatorState.contactInfo.phone);
        $('#address').val(calculatorState.contactInfo.address);
        $('#city').val(calculatorState.contactInfo.city);
        $('#state').val(calculatorState.contactInfo.state);
        $('#zip').val(calculatorState.contactInfo.zip);
        $('#project-details').val(calculatorState.contactInfo.projectDetails);
        $('#timeline').val(calculatorState.contactInfo.timeline);
        
        // Update final price display
        updateFinalPriceDisplay();
    }

    // ==============================================
    // ROOM TYPE SELECTION
    // ==============================================

    function selectRoomType($card) {
        // Get room type from data attribute
        const roomType = $card.data('room-type');
        
        if (!roomType || !roomPricing[roomType]) {
            console.error('Invalid room type:', roomType);
            return;
        }
        
        const roomName = roomPricing[roomType].name;
        
        console.log('Selected room:', roomType, roomName);
        
        // Update state
        calculatorState.selectedRoom = roomType;
        calculatorState.roomTypeName = roomName;
        
        // Update UI - remove selected from all, add to clicked
        $('.room-card').removeClass('selected');
        $card.addClass('selected');
        
        // Enable measurement section
        $('#measurement-section').removeClass('disabled');
        
        // Enable inputs
        $('#wall1, #wall2, #wall3').prop('disabled', false);
        
        // Focus first input with slight delay
        setTimeout(function() {
            $('#wall1').focus();
        }, 300);
        
        // Calculate if values already exist
        calculatePrice();
    }

    // ==============================================
    // PRICE CALCULATION
    // ==============================================

    function calculatePrice() {
        if (!calculatorState.selectedRoom) {
            console.log('No room selected yet');
            return;
        }
        
        // Get wall dimensions
        const wall1 = parseFloat($('#wall1').val()) || 0;
        const wall2 = parseFloat($('#wall2').val()) || 0;
        const wall3 = parseFloat($('#wall3').val()) || 0;
        const height = calculatorState.dimensions.height;
        
        console.log('Calculating price for walls:', wall1, wall2, wall3);
        
        // Update state
        calculatorState.dimensions = {
            wall1: wall1,
            wall2: wall2,
            wall3: wall3,
            height: height
        };
        
        // Calculate totals
        const totalLinearFeet = wall1 + wall2 + wall3;
        const totalSquareFeet = totalLinearFeet * height;
        
        calculatorState.calculatedPrice.totalLinearFeet = totalLinearFeet;
        calculatorState.calculatedPrice.totalSquareFeet = totalSquareFeet;
        
        if (totalLinearFeet > 0) {
            // Get pricing for selected room type
            const pricing = roomPricing[calculatorState.selectedRoom];
            
            // Calculate price range (based on linear feet)
            let minPrice = totalLinearFeet * pricing.minPerLinearFt;
            let maxPrice = totalLinearFeet * pricing.maxPerLinearFt;
            
            // Round to nearest hundred
            minPrice = Math.round(minPrice / 100) * 100;
            maxPrice = Math.round(maxPrice / 100) * 100;
            
            // Update state
            calculatorState.calculatedPrice.min = minPrice;
            calculatorState.calculatedPrice.max = maxPrice;
            
            console.log('Price calculated:', minPrice, '-', maxPrice);
            
            // Update UI
            updatePriceDisplay();
        } else {
            resetPriceDisplay();
        }
    }

    function updatePriceDisplay() {
        // Update total feet display
        const feetText = calculatorState.calculatedPrice.totalLinearFeet.toFixed(1) + ' ft';
        $('#total-feet span').text(feetText);
        
        // Show price display if we have measurements
        if (calculatorState.calculatedPrice.totalLinearFeet > 0) {
            const priceText = '$' + calculatorState.calculatedPrice.min.toLocaleString() + 
                            ' - $' + calculatorState.calculatedPrice.max.toLocaleString();
            $('#price-display .price-range').text(priceText);
            $('#price-display').removeClass('hidden');
        }
        
        // Enable/disable continue button based on whether required walls have values
        // Wall 3 is optional, so only require wall1 and wall2
        const wall1 = parseFloat($('#wall1').val()) || 0;
        const wall2 = parseFloat($('#wall2').val()) || 0;

        const requiredWallsComplete = wall1 > 0 && wall2 > 0;
        $('#continue-btn').prop('disabled', !requiredWallsComplete);

        console.log('Required walls complete:', requiredWallsComplete);
    }

    function resetPriceDisplay() {
        $('#total-feet span').text('0 ft');
        $('#price-display').addClass('hidden');
        $('#continue-btn').prop('disabled', true);
    }

    function updateFinalPriceDisplay() {
        const priceText = '$' + calculatorState.calculatedPrice.min.toLocaleString() + 
                        ' - $' + calculatorState.calculatedPrice.max.toLocaleString();
        $('.final-price-range').text(priceText);
        console.log('Updated final price display:', priceText);
    }

    // ==============================================
    // IMPROVED FORM VALIDATION WITH INLINE ERRORS
    // ==============================================

    function showFieldError($field, message) {
        // Add error class to field
        $field.addClass('field-error');
        
        // Remove any existing error message
        $field.siblings('.error-message').remove();
        
        // Add error message below field
        $field.after('<div class="error-message">' + message + '</div>');
    }

    function clearFieldError($field) {
        $field.removeClass('field-error');
        $field.siblings('.error-message').remove();
    }

    function validateField($field) {
        const fieldId = $field.attr('id');
        const value = $field.val().trim();
        let isValid = true;
        let errorMessage = '';

        // Clear previous errors
        clearFieldError($field);

        // Required field validation
        if ($field.prop('required') && !value) {
            errorMessage = 'This field is required';
            isValid = false;
        }
        // Email validation
        else if (fieldId === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = 'Please enter a valid email address';
                isValid = false;
            }
        }
        // Phone validation (improved)
        else if (fieldId === 'phone' && value) {
            // Remove formatting to check digits
            const digitsOnly = value.replace(/\D/g, '');
            if (digitsOnly.length !== 10) {
                errorMessage = 'Please enter a 10-digit phone number';
                isValid = false;
            }
        }
        // State validation
        else if (fieldId === 'state' && value) {
            if (value.length !== 2 || !/^[A-Z]{2}$/.test(value)) {
                errorMessage = 'Please enter a valid 2-letter state code';
                isValid = false;
            }
        }
        // Zip validation
        else if (fieldId === 'zip' && value) {
            if (!/^\d{5}(-\d{4})?$/.test(value)) {
                errorMessage = 'Please enter a valid ZIP code';
                isValid = false;
            }
        }

        if (!isValid) {
            showFieldError($field, errorMessage);
        }

        return isValid;
    }

    function validateContactForm() {
        console.log('Validating contact form');
        
        const requiredFields = ['fullName', 'address', 'city', 'state', 'zip', 'phone', 'email'];
        let isValid = true;
        let firstInvalidField = null;
        
        // Validate each required field
        requiredFields.forEach(function(fieldId) {
            const $field = $('#' + fieldId);
            if (!validateField($field)) {
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = $field;
                }
            }
        });
        
        // Scroll to first invalid field
        if (!isValid && firstInvalidField) {
            $('html, body').animate({
                scrollTop: firstInvalidField.offset().top - 100
            }, 300);
            firstInvalidField.focus();
        }
        
        return isValid;
    }

    function submitForm() {
        console.log('Submit form clicked');

        // Validate form
        if (!validateContactForm()) {
            console.log('Form validation failed');
            return;
        }

        // Check if we have calculator data
        if (!calculatorState.selectedRoom || calculatorState.calculatedPrice.min === 0) {
            alert('Please complete the calculator before submitting.');
            return;
        }

        // Validate file upload if present
        const porchImageInput = document.getElementById('porch-image');
        const porchImageFile = porchImageInput ? porchImageInput.files[0] : null;

        if (porchImageFile && porchImageFile.size > 10 * 1024 * 1024) {
            alert('Image file size must be less than 10MB. Please choose a smaller file.');
            return;
        }

        // Show loading state
        const $submitBtn = $('#submit-form');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner"></span> Submitting...').prop('disabled', true);

        // Use FormData for file upload
        const formData = new FormData();

        // Add AJAX action and nonce
        formData.append('action', 'tse_submit_calculator');
        formData.append('nonce', tseCalculator.nonce);

        // Add contact info
        formData.append('fullName', $('#fullName').val().trim());
        formData.append('email', $('#email').val().trim());
        formData.append('phone', $('#phone').val().trim());
        formData.append('address', $('#address').val().trim());
        formData.append('city', $('#city').val().trim());
        formData.append('state', $('#state').val().trim().toUpperCase());
        formData.append('zip', $('#zip').val().trim());
        formData.append('projectDetails', $('#project-details').val().trim());

        // Add room data
        formData.append('roomType', calculatorState.selectedRoom);
        formData.append('roomTypeName', calculatorState.roomTypeName);
        formData.append('wall1', calculatorState.dimensions.wall1);
        formData.append('wall2', calculatorState.dimensions.wall2);
        formData.append('wall3', calculatorState.dimensions.wall3);
        formData.append('totalLinearFeet', calculatorState.calculatedPrice.totalLinearFeet);
        formData.append('totalSquareFeet', calculatorState.calculatedPrice.totalSquareFeet);
        formData.append('priceMin', calculatorState.calculatedPrice.min);
        formData.append('priceMax', calculatorState.calculatedPrice.max);

        // Add file if present
        if (porchImageFile) {
            formData.append('porchImage', porchImageFile);
            console.log('Adding file to upload:', porchImageFile.name, '(' + (porchImageFile.size / 1024).toFixed(2) + ' KB)');
        }

        console.log('Submitting form with file upload');

        // Submit via AJAX with FormData
        $.ajax({
            url: tseCalculator.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,  // Important: don't process the data
            contentType: false,  // Important: don't set content type
            success: function(response) {
                console.log('AJAX response:', response);

                if (response.success) {
                    console.log('Form submitted successfully!');
                    // Update thank you screen
                    updateThankYouScreen();
                    // Show thank you screen
                    showScreen('thankyou');
                } else {
                    console.error('Submission failed:', response);
                    alert('Error: ' + (response.data.message || 'Submission failed. Please try again.'));
                    $submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('An error occurred. Please try again or call us directly at (910) 555-0000');
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    // ==============================================
    // UPDATE THANK YOU SCREEN
    // ==============================================

    function updateThankYouScreen() {
        // Update price range
        const priceText = '$' + calculatorState.calculatedPrice.min.toLocaleString() + 
                        ' - $' + calculatorState.calculatedPrice.max.toLocaleString();
        $('.summary-price-range').text(priceText);
        
        // Update room type
        $('#summary-room-type').text(calculatorState.roomTypeName);
        
        // Update linear feet
        $('#summary-linear-feet').text(calculatorState.calculatedPrice.totalLinearFeet.toFixed(1));
        
        console.log('Thank you screen updated');
    }

    // ==============================================
    // HELPER FUNCTIONS
    // ==============================================

    function formatPhoneNumber($input) {
        let value = $input.val().replace(/\D/g, '');
        
        if (value.length > 0) {
            if (value.length <= 3) {
                value = '(' + value;
            } else if (value.length <= 6) {
                value = '(' + value.slice(0, 3) + ') ' + value.slice(3);
            } else {
                value = '(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10);
            }
        }
        
        $input.val(value);
    }

    // ==============================================
    // GLOBAL FUNCTIONS (for onclick in HTML)
    // ==============================================

    // Make showScreen available globally for any inline onclick handlers
    window.showScreen = showScreen;

    // ==============================================
    // EVENT LISTENERS
    // ==============================================

    $(document).ready(function() {
        console.log('Sunroom Calculator initialized');
        console.log('AJAX URL:', tseCalculator.ajaxUrl);
        console.log('Starting on calculator screen');
        
        // Initialize: Show calculator screen by default
        showScreen('calculator');
        
        // Room card selection
        $('.room-card').on('click', function() {
            console.log('Room card clicked');
            selectRoomType($(this));
        });

        // Wall input changes
        $('#wall1, #wall2, #wall3').on('input change', function() {
            console.log('Wall input changed');
            calculatePrice();
        });

        // Continue to contact form button
        $('#continue-btn').on('click', function() {
            console.log('Continue button clicked');
            if (calculatorState.calculatedPrice.min > 0) {
                showScreen('contact');
                updateFinalPriceDisplay();
            }
        });

        // Back button from contact to calculator
        $('#back-to-calculator').on('click', function() {
            console.log('Back to calculator clicked');
            showScreen('calculator');
        });

        // Submit form button
        $('#submit-form').on('click', function(e) {
            e.preventDefault();
            console.log('Submit button clicked');
            submitForm();
        });

        // Phone number formatting
        $('#phone').on('input', function() {
            formatPhoneNumber($(this));
        });

        // State input - auto uppercase
        $('#state').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });

        // File input change handler - show selected filename
        $('#porch-image').on('change', function() {
            const fileName = this.files[0]?.name;
            const $helpText = $('.upload-help-text');
            if (fileName && $helpText.length) {
                $helpText.text('Selected: ' + fileName)
                         .css({
                             'font-style': 'normal',
                             'color': 'rgba(255, 255, 255, 0.95)'
                         });
            } else if ($helpText.length) {
                $helpText.text('Accepted formats: JPG, PNG, HEIC (Max 10MB)')
                         .css({
                             'font-style': 'italic',
                             'color': 'rgba(255, 255, 255, 0.7)'
                         });
            }
        });

        // Real-time validation on blur for all form fields
        $('#quote-form input, #quote-form textarea, #quote-form select').on('blur', function() {
            if ($(this).val().trim()) {
                validateField($(this));
            }
        });

        // Clear errors on focus
        $('#quote-form input, #quote-form textarea, #quote-form select').on('focus', function() {
            clearFieldError($(this));
        });

        // Enter key support for wall inputs
        $('#wall1, #wall2, #wall3').on('keypress', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                calculatePrice();
                
                // Move to next field if current has value
                const currentVal = parseFloat($(this).val()) || 0;
                if (currentVal > 0) {
                    const fieldId = $(this).attr('id');
                    if (fieldId === 'wall2') {
                        $('#wall1').focus();
                    } else if (fieldId === 'wall1') {
                        $('#wall3').focus();
                    } else if (fieldId === 'wall3') {
                        // All fields complete, focus continue button
                        $('#continue-btn').focus();
                    }
                }
            }
        });
    });

    // ==============================================
    // DEBUGGING - Remove in production
    // ==============================================
    
    // Log state changes for debugging
    window.getCalculatorState = function() {
        console.log('Current Calculator State:', calculatorState);
        return calculatorState;
    };

})(jQuery);
