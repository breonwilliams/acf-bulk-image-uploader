(function($) {
    'use strict';
    
    var ACFBIU = {
        selectedImages: [],
        detectedFields: [],
        selectedFieldIndexes: [],
        replaceExisting: true,
        mediaFrame: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $('#acfbiu-page-select').on('change', this.onPageSelect.bind(this));
            $('#acfbiu-select-images').on('click', this.openMediaLibrary.bind(this));
            $('#acfbiu-submit').on('click', this.submitImages.bind(this));
            $(document).on('click', '.acfbiu-remove-image', this.removeImage.bind(this));
            $(document).on('change', '.acfbiu-field-checkbox', this.onFieldCheckboxChange.bind(this));
            $(document).on('click', '#acfbiu-select-all-fields', this.selectAllFields.bind(this));
            $(document).on('click', '#acfbiu-select-none-fields', this.selectNoneFields.bind(this));
            $(document).on('change', '#acfbiu-replace-mode', this.onReplaceModeChange.bind(this));
            $('#acfbiu-refresh-stats').on('click', this.refreshPageStats.bind(this));
        },
        
        onPageSelect: function(e) {
            var pageId = $(e.target).val();
            
            if (!pageId) {
                $('#acfbiu-fields-section, #acfbiu-images-section, #acfbiu-submit-section').hide();
                this.selectedImages = [];
                this.detectedFields = [];
                this.selectedFieldIndexes = [];
                return;
            }
            
            this.showMessage('info', acfbiu_ajax.messages.processing);
            
            $.ajax({
                url: acfbiu_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'acfbiu_get_fields',
                    page_id: pageId,
                    nonce: acfbiu_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ACFBIU.displayFields(response.data.fields);
                        ACFBIU.detectedFields = response.data.fields;
                        
                        if (response.data.fields.length > 0) {
                            $('#acfbiu-fields-section, #acfbiu-images-section, #acfbiu-submit-section').show();
                        } else {
                            $('#acfbiu-fields-section').show();
                            $('#acfbiu-images-section, #acfbiu-submit-section').hide();
                            $('#acfbiu-fields-list').html('<p class="no-fields-message">' + 
                                (response.data.message || 'No ACF image fields found on this page.') + '</p>');
                        }
                        ACFBIU.clearMessage();
                    } else {
                        ACFBIU.showMessage('error', response.data || acfbiu_ajax.messages.error);
                    }
                },
                error: function() {
                    ACFBIU.showMessage('error', acfbiu_ajax.messages.error);
                }
            });
        },
        
        displayFields: function(fields) {
            // Initialize selected field indexes with all fields selected by default
            this.selectedFieldIndexes = fields.map(function(field, index) {
                return index;
            });
            
            var html = '<div class="acfbiu-field-selection-controls">';
            html += '<button type="button" id="acfbiu-select-all-fields" class="button button-small">Select All</button> ';
            html += '<button type="button" id="acfbiu-select-none-fields" class="button button-small">Select None</button>';
            html += '<span class="acfbiu-selected-count">Selected: <strong>' + this.selectedFieldIndexes.length + '</strong> of ' + fields.length + '</span>';
            html += '</div>';
            
            // Add replace mode toggle
            html += '<div class="acfbiu-replace-mode-wrapper">';
            html += '<label for="acfbiu-replace-mode">';
            html += '<input type="checkbox" id="acfbiu-replace-mode" ' + (this.replaceExisting ? 'checked' : '') + '> ';
            html += '<strong>Replace existing images</strong> ';
            html += '<span class="description">(Uncheck to only fill empty fields)</span>';
            html += '</label>';
            html += '</div>';
            
            html += '<div class="acfbiu-fields-grid">';
            
            fields.forEach(function(field, index) {
                var typeLabel = field.type.replace('_', ' ').replace(/\b\w/g, function(l) { 
                    return l.toUpperCase(); 
                });
                
                html += '<div class="acfbiu-field-item selected" data-field-index="' + index + '">';
                html += '<div class="field-checkbox-wrapper">';
                html += '<input type="checkbox" class="acfbiu-field-checkbox" id="field-cb-' + index + '" data-index="' + index + '" checked>';
                html += '<label for="field-cb-' + index + '"></label>';
                html += '</div>';
                html += '<div class="field-number">' + (index + 1) + '</div>';
                html += '<div class="field-info">';
                html += '<strong>' + field.label + '</strong><br>';
                html += '<span class="field-meta">Type: ' + typeLabel + '</span>';
                if (field.has_value) {
                    html += '<span class="field-status has-value">Has existing image</span>';
                    if (ACFBIU.replaceExisting) {
                        html += '<span class="field-status will-replace">Will be replaced</span>';
                    } else {
                        html += '<span class="field-status will-skip">Will be skipped</span>';
                    }
                } else {
                    html += '<span class="field-status no-value">Empty</span>';
                }
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '<p class="description">Check the fields you want to populate with images. Unchecked fields will be skipped.</p>';
            
            $('#acfbiu-fields-list').html(html);
        },
        
        openMediaLibrary: function(e) {
            e.preventDefault();
            
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }
            
            this.mediaFrame = wp.media({
                title: 'Select Images for ACF Fields',
                button: {
                    text: 'Use Selected Images'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
            
            this.mediaFrame.on('select', function() {
                var selection = ACFBIU.mediaFrame.state().get('selection');
                ACFBIU.selectedImages = [];
                
                selection.each(function(attachment) {
                    ACFBIU.selectedImages.push({
                        id: attachment.get('id'),
                        url: attachment.get('url'),
                        title: attachment.get('title'),
                        thumbnail: attachment.get('sizes').thumbnail ? 
                                  attachment.get('sizes').thumbnail.url : 
                                  attachment.get('url')
                    });
                });
                
                ACFBIU.displaySelectedImages();
            });
            
            this.mediaFrame.open();
        },
        
        displaySelectedImages: function() {
            if (this.selectedImages.length === 0) {
                $('#acfbiu-selected-images').html('');
                return;
            }
            
            var html = '<div class="acfbiu-images-grid">';
            
            this.selectedImages.forEach(function(image, index) {
                html += '<div class="acfbiu-image-item" data-image-index="' + index + '">';
                html += '<div class="image-number">' + (index + 1) + '</div>';
                html += '<img src="' + image.thumbnail + '" alt="' + image.title + '">';
                html += '<button type="button" class="acfbiu-remove-image" data-index="' + index + '">&times;</button>';
                html += '<div class="image-title">' + image.title + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '<p class="description">Images selected: <strong>' + this.selectedImages.length + '</strong></p>';
            
            $('#acfbiu-selected-images').html(html);
            
            // Show warning based on selected fields count
            if (this.selectedFieldIndexes.length > 0) {
                var warningHtml = '';
                if (this.selectedImages.length > this.selectedFieldIndexes.length) {
                    warningHtml = '<p class="notice notice-warning">You have selected more images (' + this.selectedImages.length + 
                                 ') than selected fields (' + this.selectedFieldIndexes.length + '). Extra images will be ignored.</p>';
                } else if (this.selectedImages.length < this.selectedFieldIndexes.length) {
                    warningHtml = '<p class="notice notice-info">You have selected fewer images (' + this.selectedImages.length + 
                                 ') than selected fields (' + this.selectedFieldIndexes.length + '). Some fields will remain empty.</p>';
                } else {
                    warningHtml = '<p class="notice notice-success">Perfect match! ' + this.selectedImages.length + 
                                 ' images will be assigned to ' + this.selectedFieldIndexes.length + ' selected fields.</p>';
                }
                if (warningHtml) {
                    $('#acfbiu-selected-images').append(warningHtml);
                }
            } else if (this.detectedFields.length > 0) {
                $('#acfbiu-selected-images').append('<p class="notice notice-warning">No fields selected. Please select at least one field to upload images.</p>');
            }
        },
        
        removeImage: function(e) {
            e.preventDefault();
            var index = $(e.target).data('index');
            this.selectedImages.splice(index, 1);
            this.displaySelectedImages();
        },
        
        onFieldCheckboxChange: function(e) {
            var checkbox = $(e.target);
            var index = parseInt(checkbox.data('index'));
            var fieldItem = checkbox.closest('.acfbiu-field-item');
            
            if (checkbox.is(':checked')) {
                // Add to selected indexes if not already present
                if (this.selectedFieldIndexes.indexOf(index) === -1) {
                    this.selectedFieldIndexes.push(index);
                    this.selectedFieldIndexes.sort(function(a, b) { return a - b; });
                }
                fieldItem.addClass('selected');
            } else {
                // Remove from selected indexes
                var pos = this.selectedFieldIndexes.indexOf(index);
                if (pos > -1) {
                    this.selectedFieldIndexes.splice(pos, 1);
                }
                fieldItem.removeClass('selected');
            }
            
            this.updateSelectedFieldCount();
            this.displaySelectedImages(); // Update warnings based on new selection
        },
        
        selectAllFields: function(e) {
            e.preventDefault();
            $('.acfbiu-field-checkbox').prop('checked', true);
            $('.acfbiu-field-item').addClass('selected');
            this.selectedFieldIndexes = this.detectedFields.map(function(field, index) {
                return index;
            });
            this.updateSelectedFieldCount();
            this.displaySelectedImages();
        },
        
        selectNoneFields: function(e) {
            e.preventDefault();
            $('.acfbiu-field-checkbox').prop('checked', false);
            $('.acfbiu-field-item').removeClass('selected');
            this.selectedFieldIndexes = [];
            this.updateSelectedFieldCount();
            this.displaySelectedImages();
        },
        
        updateSelectedFieldCount: function() {
            $('.acfbiu-selected-count').html('Selected: <strong>' + this.selectedFieldIndexes.length + '</strong> of ' + this.detectedFields.length);
        },
        
        onReplaceModeChange: function(e) {
            this.replaceExisting = $(e.target).is(':checked');
            
            // Update field display to show new status
            $('.acfbiu-field-item').each(function(index, element) {
                var $fieldItem = $(element);
                var fieldIndex = parseInt($fieldItem.data('field-index'));
                var field = ACFBIU.detectedFields[fieldIndex];
                
                if (field && field.has_value) {
                    var $fieldInfo = $fieldItem.find('.field-info');
                    
                    // Remove old status indicators
                    $fieldInfo.find('.will-replace, .will-skip').remove();
                    
                    // Add new status indicator
                    if (ACFBIU.replaceExisting) {
                        $fieldInfo.append('<span class="field-status will-replace">Will be replaced</span>');
                    } else {
                        $fieldInfo.append('<span class="field-status will-skip">Will be skipped</span>');
                    }
                }
            });
            
            // Update selected field indexes if in skip mode
            if (!this.replaceExisting) {
                this.updateSelectedForSkipMode();
            } else {
                // Re-enable all checkboxes
                $('.acfbiu-field-checkbox').prop('disabled', false);
            }
        },
        
        updateSelectedForSkipMode: function() {
            // In skip mode, automatically uncheck and disable fields with existing values
            if (!this.replaceExisting) {
                this.detectedFields.forEach(function(field, index) {
                    if (field.has_value) {
                        var $checkbox = $('#field-cb-' + index);
                        $checkbox.prop('checked', false).prop('disabled', true);
                        $('.acfbiu-field-item[data-field-index="' + index + '"]').removeClass('selected');
                        
                        // Remove from selected indexes
                        var pos = ACFBIU.selectedFieldIndexes.indexOf(index);
                        if (pos > -1) {
                            ACFBIU.selectedFieldIndexes.splice(pos, 1);
                        }
                    }
                });
                this.updateSelectedFieldCount();
                this.displaySelectedImages();
            }
        },
        
        refreshPageStats: function(e) {
            e.preventDefault();
            var $button = $(e.target);
            $button.prop('disabled', true).text('Refreshing...');
            
            $.ajax({
                url: acfbiu_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'acfbiu_get_page_stats',
                    nonce: acfbiu_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update page dropdown options with new stats
                        $('#acfbiu-page-select option').each(function() {
                            var $option = $(this);
                            var pageId = $option.val();
                            
                            if (pageId && response.data[pageId]) {
                                var stats = response.data[pageId];
                                var text = $option.text();
                                // Remove old stats from text
                                text = text.replace(/ — \d+ empty \/ \d+ total.*$/, '');
                                
                                // Add new stats
                                if (stats.total > 0) {
                                    text += ' — ' + stats.empty + ' empty / ' + stats.total + ' total';
                                    if (stats.empty === 0) {
                                        text += ' ✓';
                                    }
                                }
                                
                                $option.text(text);
                                $option.attr({
                                    'data-empty': stats.empty,
                                    'data-total': stats.total,
                                    'data-filled': stats.filled
                                });
                            }
                        });
                        
                        // Clear transient cache
                        ACFBIU.showMessage('success', 'Page statistics refreshed successfully!');
                    }
                    
                    $button.prop('disabled', false).text('Refresh Stats');
                },
                error: function() {
                    $button.prop('disabled', false).text('Refresh Stats');
                    ACFBIU.showMessage('error', 'Failed to refresh statistics.');
                }
            });
        },
        
        submitImages: function(e) {
            e.preventDefault();
            
            var pageId = $('#acfbiu-page-select').val();
            
            if (!pageId) {
                this.showMessage('error', acfbiu_ajax.messages.select_page);
                return;
            }
            
            if (this.selectedImages.length === 0) {
                this.showMessage('error', acfbiu_ajax.messages.select_images);
                return;
            }
            
            if (this.selectedFieldIndexes.length === 0) {
                this.showMessage('error', 'Please select at least one field to upload images to.');
                return;
            }
            
            // Prepare assignments - only for selected fields
            var assignments = [];
            var imageIndex = 0;
            
            for (var i = 0; i < this.selectedFieldIndexes.length && imageIndex < this.selectedImages.length; i++) {
                var fieldIndex = this.selectedFieldIndexes[i];
                var field = this.detectedFields[fieldIndex];
                
                if (field.type === 'gallery') {
                    // Gallery fields can accept multiple images
                    var galleryImages = [];
                    while (imageIndex < this.selectedImages.length) {
                        galleryImages.push(this.selectedImages[imageIndex].id);
                        imageIndex++;
                    }
                    
                    assignments.push({
                        field_key: field.key,
                        field_type: field.type,
                        field_name: field.name,
                        attachment_ids: galleryImages,
                        parent_key: field.parent_key || '',
                        parent_name: field.parent_name || '',
                        parent_hierarchy: field.parent_hierarchy || [],
                        layout_name: field.layout_name || ''
                    });
                } else if (field.type === 'repeater_image' || field.type === 'nested_repeater_image') {
                    // Repeater fields - create one row per image
                    var repeaterImages = [];
                    while (imageIndex < this.selectedImages.length && repeaterImages.length < 10) { // Limit to 10 rows
                        repeaterImages.push(this.selectedImages[imageIndex].id);
                        imageIndex++;
                    }
                    
                    assignments.push({
                        field_key: field.key,
                        field_type: field.type,
                        field_name: field.name,
                        attachment_ids: repeaterImages,
                        parent_key: field.parent_key || '',
                        parent_name: field.parent_name || '',
                        parent_hierarchy: field.parent_hierarchy || [],
                        layout_name: field.layout_name || ''
                    });
                } else {
                    // Single image field
                    assignments.push({
                        field_key: field.key,
                        field_type: field.type,
                        field_name: field.name,
                        attachment_ids: [this.selectedImages[imageIndex].id],
                        parent_key: field.parent_key || '',
                        parent_name: field.parent_name || '',
                        parent_hierarchy: field.parent_hierarchy || [],
                        layout_name: field.layout_name || ''
                    });
                    imageIndex++;
                }
            }
            
            // Disable submit button
            $('#acfbiu-submit').prop('disabled', true);
            this.showMessage('info', acfbiu_ajax.messages.processing);
            
            $.ajax({
                url: acfbiu_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'acfbiu_upload_images',
                    page_id: pageId,
                    assignments: assignments,
                    nonce: acfbiu_ajax.nonce
                },
                success: function(response) {
                    $('#acfbiu-submit').prop('disabled', false);
                    
                    if (response.success) {
                        ACFBIU.showMessage('success', response.data.message);
                        // Clear selected images after successful upload
                        ACFBIU.selectedImages = [];
                        ACFBIU.displaySelectedImages();
                        // Refresh fields to show updated status
                        $('#acfbiu-page-select').trigger('change');
                    } else {
                        ACFBIU.showMessage('error', response.data || acfbiu_ajax.messages.error);
                    }
                },
                error: function() {
                    $('#acfbiu-submit').prop('disabled', false);
                    ACFBIU.showMessage('error', acfbiu_ajax.messages.error);
                }
            });
        },
        
        showMessage: function(type, message) {
            var html = '<div class="notice notice-' + type + '"><p>' + message + '</p></div>';
            $('#acfbiu-message').html(html);
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $('#acfbiu-message').fadeOut(function() {
                        $(this).html('').show();
                    });
                }, 5000);
            }
        },
        
        clearMessage: function() {
            $('#acfbiu-message').html('');
        }
    };
    
    $(document).ready(function() {
        ACFBIU.init();
    });
    
})(jQuery);