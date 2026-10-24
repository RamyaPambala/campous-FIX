/**
 * CampusFix — Main Application JavaScript
 * Handles UI interactions, form validation, mobile navigation, and dynamic components.
 */

'use strict';

// ── DOM Ready ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initAlerts();
    initMobileNav();
    initSidebar();
    initCharCounters();
    initStarRating();
    initConfirmActions();
    initFileUpload();
    initFormValidation();
    initSearchDebounce();
    initModalSystem();
    initScrollSpy();
});

// ── Alert Dismissal ────────────────────────────────────────
function initAlerts() {
    document.querySelectorAll('.alert-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var alert = this.closest('.alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            }
        });
    });

    // Auto-dismiss success alerts after 5 seconds
    document.querySelectorAll('.alert-success').forEach(function (alert) {
        setTimeout(function () {
            if (alert && alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    if (alert.parentNode) alert.remove();
                }, 300);
            }
        }, 5000);
    });
}

// ── Public Navbar Mobile Toggle ────────────────────────────
function initMobileNav() {
    var toggle = document.querySelector('.navbar-toggle');
    var links = document.querySelector('.navbar-links');

    if (toggle && links) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            links.classList.toggle('open');
            var isOpen = links.classList.contains('open');
            toggle.setAttribute('aria-expanded', isOpen);
            toggle.innerHTML = isOpen ? '&times;' : '&#9776;';
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '&#9776;';
            }
        });
    }
}

// ── Sidebar Toggle (Mobile) ───────────────────────────────
function initSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    var openBtns = document.querySelectorAll('.mobile-menu-btn');
    var closeBtns = document.querySelectorAll('.sidebar-close');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    openBtns.forEach(function (btn) {
        btn.addEventListener('click', openSidebar);
    });

    closeBtns.forEach(function (btn) {
        btn.addEventListener('click', closeSidebar);
    });

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
}

// ── Character Counters ─────────────────────────────────────
function initCharCounters() {
    document.querySelectorAll('[data-maxlength]').forEach(function (el) {
        var max = parseInt(el.getAttribute('data-maxlength'), 10);
        if (isNaN(max)) return;

        // Create counter element
        var counter = document.createElement('span');
        counter.className = 'char-counter';
        counter.style.cssText = 'display:block;text-align:right;font-size:0.8rem;color:#94a3b8;margin-top:4px;';
        el.parentNode.appendChild(counter);

        function update() {
            var len = el.value.length;
            counter.textContent = len + ' / ' + max;
            if (len > max * 0.9) {
                counter.style.color = '#d97706';
            } else if (len > max) {
                counter.style.color = '#dc2626';
            } else {
                counter.style.color = '#94a3b8';
            }
        }

        el.addEventListener('input', update);
        update();
    });
}

// ── Star Rating ────────────────────────────────────────────
function initStarRating() {
    document.querySelectorAll('.star-rating-input').forEach(function (container) {
        var input = container.querySelector('input[type="hidden"]') ||
                    container.querySelector('input[name="rating"]');
        var stars = container.querySelectorAll('.star');

        stars.forEach(function (star, index) {
            star.addEventListener('click', function () {
                var value = parseInt(this.getAttribute('data-value'), 10);
                if (input) input.value = value;

                stars.forEach(function (s, i) {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseenter', function () {
                var value = parseInt(this.getAttribute('data-value'), 10);
                stars.forEach(function (s, i) {
                    if (i < value) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });

            star.addEventListener('mouseleave', function () {
                stars.forEach(function (s) {
                    s.classList.remove('hover');
                });
            });

            // Keyboard accessible
            star.setAttribute('tabindex', '0');
            star.setAttribute('role', 'radio');
            star.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    });
}

// ── Confirm Important Actions ──────────────────────────────
function initConfirmActions() {
    // Only attach click listeners to explicit action buttons/links, never forms or containers
    document.querySelectorAll('button[data-confirm], a[data-confirm], input[type="submit"][data-confirm], input[type="button"][data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Forms with data-confirm: intercept submit event only (not bubbling clicks)
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// ── File Upload Preview ────────────────────────────────────
function initFileUpload() {
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        var previewId = input.getAttribute('data-preview');
        var preview = document.getElementById(previewId);
        if (!preview) return;

        input.addEventListener('change', function () {
            preview.innerHTML = '';

            if (this.files && this.files[0]) {
                var file = this.files[0];

                // Check size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    preview.innerHTML = '<span style="color:#dc2626;font-size:0.875rem;">File is too large. Maximum size is 2 MB.</span>';
                    this.value = '';
                    return;
                }

                // Check type
                var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (allowed.indexOf(file.type) === -1) {
                    preview.innerHTML = '<span style="color:#dc2626;font-size:0.875rem;">Invalid file type. Only JPG, PNG, GIF, and WebP allowed.</span>';
                    this.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Upload preview';
                    img.style.cssText = 'max-width:200px;max-height:200px;border-radius:8px;margin-top:8px;border:1px solid #e2e8f0;';
                    preview.appendChild(img);

                    var info = document.createElement('span');
                    info.style.cssText = 'display:block;font-size:0.8rem;color:#64748b;margin-top:4px;';
                    info.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                    preview.appendChild(info);
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// ── Client-Side Form Validation ────────────────────────────
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;

            // Clear previous errors
            form.querySelectorAll('.form-error').forEach(function (err) {
                err.textContent = '';
                err.style.display = 'none';
            });
            form.querySelectorAll('.form-control.error, .form-select.error').forEach(function (el) {
                el.classList.remove('error');
            });

            // Validate required fields
            form.querySelectorAll('[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    valid = false;
                    markFieldError(field, 'This field is required.');
                }
            });

            // Validate email fields
            form.querySelectorAll('input[type="email"]').forEach(function (field) {
                if (field.value && !isValidEmail(field.value)) {
                    valid = false;
                    markFieldError(field, 'Please enter a valid email address.');
                }
            });

            // Validate min length
            form.querySelectorAll('[minlength]').forEach(function (field) {
                var min = parseInt(field.getAttribute('minlength'), 10);
                if (field.value && field.value.length < min) {
                    valid = false;
                    markFieldError(field, 'Must be at least ' + min + ' characters.');
                }
            });

            // Validate password confirmation
            var password = form.querySelector('input[name="password"]');
            var confirm = form.querySelector('input[name="confirm_password"]');
            if (password && confirm && password.value !== confirm.value) {
                valid = false;
                markFieldError(confirm, 'Passwords do not match.');
            }

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                var firstError = form.querySelector('.form-control.error, .form-select.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });
}

function markFieldError(field, message) {
    field.classList.add('error');
    var errorEl = field.parentNode.querySelector('.form-error');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'form-error';
        field.parentNode.appendChild(errorEl);
    }
    errorEl.textContent = message;
    errorEl.style.display = 'block';
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ── Search Debounce ────────────────────────────────────────
function initSearchDebounce() {
    // Auto-submit filter forms on select change
    document.querySelectorAll('.filter-bar select').forEach(function (select) {
        select.addEventListener('change', function () {
            var form = this.closest('form');
            if (form && form.classList.contains('auto-submit')) {
                form.submit();
            }
        });
    });
}

// ── Modal System ───────────────────────────────────────────
function initModalSystem() {
    // Open modal
    document.querySelectorAll('[data-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var modalId = this.getAttribute('data-modal');
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    document.querySelectorAll('.modal-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var overlay = this.closest('.modal-overlay');
            if (overlay) closeModal(overlay);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) closeModal(activeModal);
        }
    });
}

function closeModal(overlay) {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// ── Smooth Scroll for Anchor Links ─────────────────────────
function initScrollSpy() {
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Close mobile nav if open
                var navLinks = document.querySelector('.navbar-links');
                if (navLinks) navLinks.classList.remove('open');
            }
        });
    });
}

// ── Profile Edit Toggle ───────────────────────────────────
function toggleProfileEdit() {
    var displaySection = document.getElementById('profile-display');
    var editSection = document.getElementById('profile-edit');

    if (displaySection && editSection) {
        var isEditing = editSection.style.display !== 'none';
        displaySection.style.display = isEditing ? 'block' : 'none';
        editSection.style.display = isEditing ? 'none' : 'block';
    }
}

// ── Dynamic Status Change Form ─────────────────────────────
function onStatusChange(select) {
    var resolutionGroup = document.getElementById('resolution-group');
    if (resolutionGroup) {
        if (select.value === 'Resolved') {
            resolutionGroup.style.display = 'block';
        } else {
            resolutionGroup.style.display = 'none';
        }
    }
}

// ── Prevent Double Submission ──────────────────────────────
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitBtns.forEach(function (btn) {
            // Delay disabling so form data is captured
            setTimeout(function () {
                btn.disabled = true;
                if (btn.tagName === 'BUTTON') {
                    btn.dataset.originalText = btn.innerHTML;
                    btn.innerHTML = 'Processing...';
                }
            }, 10);
        });

        // Re-enable after 5 seconds in case of error
        setTimeout(function () {
            submitBtns.forEach(function (btn) {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                }
            });
        }, 5000);
    });
});

// ── Admin Dashboard HTML5 Canvas Charts ────────────────────
function initDashboardCharts(categoryData, monthlyData) {
    renderCategoryChart(categoryData);
    renderMonthlyTrendChart(monthlyData);

    // Responsive redraw on window resize
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            renderCategoryChart(categoryData);
            renderMonthlyTrendChart(monthlyData);
        }, 120);
    });
}

function setupChartCanvas(canvasId) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    var container = canvas.parentElement;
    var width = container.clientWidth || 400;
    var height = container.clientHeight || 260;
    var dpr = window.devicePixelRatio || 1;

    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';

    var ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    return { ctx: ctx, width: width, height: height };
}

function renderCategoryChart(data) {
    var setup = setupChartCanvas('categoryChart');
    if (!setup) return;
    var ctx = setup.ctx;
    var w = setup.width;
    var h = setup.height;

    ctx.clearRect(0, 0, w, h);

    if (!data || data.length === 0) {
        ctx.fillStyle = '#94a3b8';
        ctx.font = '14px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('No category data available', w / 2, h / 2);
        return;
    }

    var maxVal = 1;
    data.forEach(function (d) {
        var c = parseInt(d.count, 10) || 0;
        if (c > maxVal) maxVal = c;
    });

    var paddingLeft = 145;
    var paddingRight = 45;
    var paddingTop = 16;
    var paddingBottom = 16;
    var plotW = Math.max(50, w - paddingLeft - paddingRight);
    var plotH = Math.max(50, h - paddingTop - paddingBottom);
    var rowH = plotH / data.length;
    var barH = Math.min(20, rowH * 0.55);

    // Draw grid lines
    ctx.strokeStyle = '#f1f5f9';
    ctx.lineWidth = 1;
    for (var i = 0; i <= 4; i++) {
        var x = paddingLeft + (plotW / 4) * i;
        ctx.beginPath();
        ctx.moveTo(x, paddingTop);
        ctx.lineTo(x, h - paddingBottom);
        ctx.stroke();
    }

    data.forEach(function (d, idx) {
        var yCenter = paddingTop + rowH * idx + rowH / 2;
        var barY = yCenter - barH / 2;
        var val = parseInt(d.count, 10) || 0;
        var barW = maxVal > 0 ? (val / maxVal) * plotW : 0;
        if (barW < 4 && val > 0) barW = 4;

        // Label
        ctx.fillStyle = '#334155';
        ctx.font = '500 12px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        var labelText = d.category || '';
        if (labelText.length > 18) labelText = labelText.substring(0, 16) + '...';
        ctx.fillText(labelText, paddingLeft - 10, yCenter);

        // Background track
        ctx.fillStyle = '#f8fafc';
        drawRoundedRect(ctx, paddingLeft, barY, plotW, barH, 5);
        ctx.fill();

        // Bar fill with gradient
        if (barW > 0) {
            var grad = ctx.createLinearGradient(paddingLeft, 0, paddingLeft + barW, 0);
            grad.addColorStop(0, '#1e40af');
            grad.addColorStop(1, '#3b82f6');
            ctx.fillStyle = grad;
            drawRoundedRect(ctx, paddingLeft, barY, barW, barH, 5);
            ctx.fill();
        }

        // Count number badge
        ctx.fillStyle = val > 0 ? '#1e40af' : '#94a3b8';
        ctx.font = '700 12px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(val.toString(), paddingLeft + barW + 8, yCenter);
    });
}

function renderMonthlyTrendChart(data) {
    var setup = setupChartCanvas('monthlyTrendChart');
    if (!setup) return;
    var ctx = setup.ctx;
    var w = setup.width;
    var h = setup.height;

    ctx.clearRect(0, 0, w, h);

    if (!data || data.length === 0) {
        ctx.fillStyle = '#94a3b8';
        ctx.font = '14px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('No trend data available', w / 2, h / 2);
        return;
    }

    var maxVal = 2;
    data.forEach(function (d) {
        var c = parseInt(d.count, 10) || 0;
        if (c > maxVal) maxVal = c;
    });
    maxVal = Math.ceil(maxVal * 1.25);
    if (maxVal < 3) maxVal = 3;

    var paddingLeft = 35;
    var paddingRight = 25;
    var paddingTop = 25;
    var paddingBottom = 35;
    var plotW = Math.max(50, w - paddingLeft - paddingRight);
    var plotH = Math.max(50, h - paddingTop - paddingBottom);

    // Horizontal grid lines
    var steps = 3;
    ctx.strokeStyle = '#f1f5f9';
    ctx.lineWidth = 1;
    ctx.font = '500 11px system-ui, -apple-system, sans-serif';
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';

    for (var s = 0; s <= steps; s++) {
        var gridY = paddingTop + (plotH / steps) * (steps - s);
        var stepVal = Math.round((maxVal / steps) * s);
        ctx.beginPath();
        ctx.moveTo(paddingLeft, gridY);
        ctx.lineTo(w - paddingRight, gridY);
        ctx.stroke();

        ctx.fillText(stepVal.toString(), paddingLeft - 8, gridY);
    }

    // Calculate points
    var points = [];
    var stepX = data.length > 1 ? plotW / (data.length - 1) : plotW / 2;

    data.forEach(function (d, i) {
        var x = data.length > 1 ? paddingLeft + i * stepX : paddingLeft + plotW / 2;
        var val = parseInt(d.count, 10) || 0;
        var y = paddingTop + plotH - (val / maxVal) * plotH;
        points.push({ x: x, y: y, val: val, label: d.label || d.month });
    });

    // Area fill with gradient
    if (points.length > 0) {
        var areaGrad = ctx.createLinearGradient(0, paddingTop, 0, paddingTop + plotH);
        areaGrad.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
        areaGrad.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

        ctx.beginPath();
        ctx.moveTo(points[0].x, paddingTop + plotH);
        points.forEach(function (p) {
            ctx.lineTo(p.x, p.y);
        });
        ctx.lineTo(points[points.length - 1].x, paddingTop + plotH);
        ctx.closePath();
        ctx.fillStyle = areaGrad;
        ctx.fill();

        // Stroke line
        ctx.beginPath();
        points.forEach(function (p, i) {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 3;
        ctx.lineJoin = 'round';
        ctx.stroke();
    }

    // Draw dots and value labels
    points.forEach(function (p) {
        // Outer dot
        ctx.beginPath();
        ctx.arc(p.x, p.y, 4.5, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = '#2563eb';
        ctx.stroke();

        // Value text
        ctx.fillStyle = '#1e3a8a';
        ctx.font = '700 11px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText(p.val.toString(), p.x, p.y - 7);

        // X-axis label
        ctx.fillStyle = '#64748b';
        ctx.font = '500 11px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        var shortMonth = p.label.split(' ')[0] || p.label;
        ctx.fillText(shortMonth, p.x, paddingTop + plotH + 8);
    });
}

function drawRoundedRect(ctx, x, y, width, height, radius) {
    if (width <= 0 || height <= 0) return;
    if (radius > width / 2) radius = width / 2;
    if (radius > height / 2) radius = height / 2;
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
}
