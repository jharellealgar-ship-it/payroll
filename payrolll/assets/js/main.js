/**
 * Professional Payroll Management System
 * Main JavaScript File
 */

$(document).ready(function() {
    // Initialize DataTables
    if ($('.data-table').length) {
        $('.data-table').each(function() {
            // Check if DataTable is already initialized
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries found",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }
        });
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.btn-delete, .delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Currency formatting
    $('.currency-input').on('blur', function() {
        let value = parseFloat($(this).val()) || 0;
        $(this).val(value.toFixed(2));
    });

    // Time picker initialization (if using time picker)
    if ($('.time-picker').length) {
        $('.time-picker').attr('type', 'time');
    }

    // Calculate hours automatically
    $('.calculate-hours').on('change', function() {
        let timeIn = $('.time-in').val();
        let timeOut = $('.time-out').val();
        let breakMinutes = parseFloat($('.break-duration').val()) || 0;
        
        if (timeIn && timeOut) {
            let start = new Date('2000-01-01 ' + timeIn);
            let end = new Date('2000-01-01 ' + timeOut);
            let diff = (end - start) / 1000 / 60; // difference in minutes
            let totalMinutes = diff - breakMinutes;
            let hours = Math.max(0, (totalMinutes / 60).toFixed(2));
            $('.total-hours').val(hours);
        }
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        let input = $(this).siblings('input');
        let icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
});

