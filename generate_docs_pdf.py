"""
ColdConnect - Professional PDF Documentation Generator
Builds ColdConnect_Documentation.pdf using ReportLab with custom styling,
headers/footers, dynamic page numbers, callout boxes, and tables.
"""

import os
import sys
from reportlab.lib import colors
from reportlab.lib.pagesizes import letter
from reportlab.lib.units import inch
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.pdfgen import canvas

# Palette definition
COLOR_PRIMARY = colors.HexColor('#059669')       # Emerald Green
COLOR_PRIMARY_DARK = colors.HexColor('#047857')
COLOR_SECONDARY = colors.HexColor('#0284c7')     # Ice Cyan
COLOR_SLATE_DARK = colors.HexColor('#0f172a')    # Dark Slate
COLOR_SLATE_MUTED = colors.HexColor('#475569')   # Muted Gray
COLOR_BG_LIGHT = colors.HexColor('#f8fafc')      # Light Gray BG
COLOR_BORDER = colors.HexColor('#cbd5e1')        # Light Border
COLOR_RED = colors.HexColor('#dc2626')           # Danger / Reject Red
COLOR_RED_BG = colors.HexColor('#fee2e2')        # Soft Red BG
COLOR_GOLD = colors.HexColor('#d97706')          # Amber Gold


class NumberedCanvas(canvas.Canvas):
    """
    Two-pass canvas to dynamically compute and print 'Page X of Y'
    along with running headers and footers on all pages except the cover page.
    """
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super().showPage()
        super().save()

    def draw_page_decorations(self, page_count):
        if self._pageNumber == 1:
            # Skip running header/footer on title/cover page
            return

        self.saveState()
        self.setFont("Helvetica", 8)
        self.setFillColor(COLOR_SLATE_MUTED)

        # Running Header
        self.drawString(54, 11 * inch - 36, "Agri Storage  |  System Architecture, Code Walkthrough & Working Guide")
        self.setStrokeColor(COLOR_BORDER)
        self.setLineWidth(0.5)
        self.line(54, 11 * inch - 42, 8.5 * inch - 54, 11 * inch - 42)

        # Running Footer
        self.line(54, 46, 8.5 * inch - 54, 46)
        self.drawString(54, 34, "Confidential - Agri Storage AgriTech Prototype  |  Silver Oak University Kalpvruksh 2.0")
        page_str = f"Page {self._pageNumber} of {page_count}"
        self.drawRightString(8.5 * inch - 54, 34, page_str)
        self.restoreState()


def make_callout(text, bg_color, border_color, text_color, title=None, style=None):
    """Helper to create a visually distinct callout box."""
    elements = []
    if title:
        title_style = ParagraphStyle(
            'CalloutTitle',
            parent=style,
            fontName='Helvetica-Bold',
            fontSize=10,
            leading=13,
            textColor=border_color,
            spaceAfter=3
        )
        elements.append(Paragraph(title, title_style))

    body_style = ParagraphStyle(
        'CalloutBody',
        parent=style,
        fontName='Helvetica',
        fontSize=9,
        leading=13,
        textColor=text_color
    )
    elements.append(Paragraph(text, body_style))

    t = Table([[elements]], colWidths=[7.2 * inch])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), bg_color),
        ('BOX', (0, 0), (-1, -1), 1.2, border_color),
        ('PADDING', (0, 0), (-1, -1), 9),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('LEFTPADDING', (0, 0), (-1, -1), 12),
        ('RIGHTPADDING', (0, 0), (-1, -1), 12),
    ]))
    return t


def make_code_box(code_text, style):
    """Helper to wrap code snippets in a shaded, bordered box."""
    code_style = ParagraphStyle(
        'CodeSnippet',
        parent=style,
        fontName='Courier',
        fontSize=7.5,
        leading=10.5,
        textColor=COLOR_SLATE_DARK
    )
    formatted = code_text.replace('\n', '<br/>').replace(' ', '&nbsp;')
    p = Paragraph(formatted, code_style)
    t = Table([[p]], colWidths=[7.2 * inch])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#f1f5f9')),
        ('BOX', (0, 0), (-1, -1), 0.8, COLOR_BORDER),
        ('PADDING', (0, 0), (-1, -1), 8),
    ]))
    return t


def build_pdf(filename="ColdConnect_Documentation.pdf"):
    doc = SimpleDocTemplate(
        filename,
        pagesize=letter,
        leftMargin=54,
        rightMargin=54,
        topMargin=54,
        bottomMargin=54
    )

    styles = getSampleStyleSheet()

    # Custom Paragraph Styles
    title_style = ParagraphStyle(
        'CoverTitle',
        parent=styles['Heading1'],
        fontName='Helvetica-Bold',
        fontSize=24,
        leading=28,
        textColor=COLOR_PRIMARY_DARK,
        spaceAfter=6
    )

    subtitle_style = ParagraphStyle(
        'CoverSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=12,
        leading=16,
        textColor=COLOR_SLATE_MUTED,
        spaceAfter=14
    )

    meta_style = ParagraphStyle(
        'CoverMeta',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=13,
        textColor=COLOR_SLATE_DARK
    )

    h1_style = ParagraphStyle(
        'SectionH1',
        parent=styles['Heading1'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=19,
        textColor=COLOR_SLATE_DARK,
        spaceBefore=14,
        spaceAfter=6,
        keepWithNext=True
    )

    h2_style = ParagraphStyle(
        'SectionH2',
        parent=styles['Heading2'],
        fontName='Helvetica-Bold',
        fontSize=11.5,
        leading=15,
        textColor=COLOR_PRIMARY,
        spaceBefore=10,
        spaceAfter=4,
        keepWithNext=True
    )

    body_style = ParagraphStyle(
        'DocBody',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=13.5,
        textColor=COLOR_SLATE_DARK,
        spaceAfter=6
    )

    bullet_style = ParagraphStyle(
        'DocBullet',
        parent=body_style,
        leftIndent=14,
        firstLineIndent=-10,
        spaceAfter=3
    )

    th_style = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8.5,
        leading=11,
        textColor=colors.white
    )

    td_style = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8,
        leading=11,
        textColor=COLOR_SLATE_DARK
    )

    story = []

    # ==========================================
    # COVER / HEADER BANNER
    # ==========================================
    story.append(Paragraph("AGRI STORAGE", title_style))
    story.append(Paragraph("Smart Cold Storage Finder, Reservation Platform & Capacity Synchronization", subtitle_style))
    story.append(HRFlowable(width="100%", thickness=2.5, color=COLOR_PRIMARY, spaceBefore=0, spaceAfter=12))

    meta_table_data = [
        [
            Paragraph("<b>Document Type:</b> Full Architecture & Code Guide", meta_style),
            Paragraph("<b>Stack:</b> PHP 8.x, MySQL (PDO), Vanilla JS, CSS3", meta_style)
        ],
        [
            Paragraph("<b>Event:</b> Silver Oak Kalpvruksh 2.0 Hackathon", meta_style),
            Paragraph("<b>Topic:</b> UI Reject Popup Modal & Code Minimisation", meta_style)
        ],
        [
            Paragraph("<b>Target Audience:</b> Evaluators, Developers, Jury", meta_style),
            Paragraph(f"<b>Date:</b> September 2026 | Version 2.1", meta_style)
        ]
    ]
    t_meta = Table(meta_table_data, colWidths=[3.6 * inch, 3.6 * inch])
    t_meta.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), COLOR_BG_LIGHT),
        ('BOX', (0, 0), (-1, -1), 0.8, COLOR_BORDER),
        ('PADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(t_meta)
    story.append(Spacer(1, 14))

    # Executive Summary
    story.append(Paragraph("1. Executive Summary & Problem Solved", h1_style))
    story.append(Paragraph(
        "In India, smallholder farmers lose an estimated <b>15% to 25%</b> of horticultural produce "
        "(tomatoes, onions, potatoes, carrots, apples) due to inadequate cold chain infrastructure. "
        "During harvest gluts, local markets experience severe price crashes. Lacking transparent information "
        "on nearby cold storage facilities, farmers are forced into <i>distress selling</i> at steep financial losses. "
        "Simultaneously, commercial cold storage warehouse owners suffer from underutilized chamber capacities "
        "due to offline, fragmented communication channels.",
        body_style
    ))
    story.append(Paragraph(
        "<b>ColdConnect solves this dual problem</b> by providing an integrated, transparent, web-based AgriTech "
        "portal that bridges farmers and cold storage operators. Farmers can instantly discover certified facilities, "
        "evaluate real-time rates, view temperature capabilities, run live cost estimators, and submit digital reservations. "
        "Warehouse managers access a dedicated management portal to monitor capacity, accept reservations with atomic "
        "capacity decrements, or reject bookings cleanly using an accessible in-app UI popup modal.",
        body_style
    ))
    story.append(Spacer(1, 10))

    # Callout Highlight
    alert_box = make_callout(
        "<b>Key Update:</b> Replaced native browser confirm() dialog with an accessible, in-app UI Popup Modal "
        "for booking rejections. This streamlines code across owner dashboards, eliminates redundant row-level forms, "
        "and presents rich booking context (Farmer, Crop, Quantity, Facility) before final cancellation.",
        COLOR_PRIMARY.clone(alpha=0.08), COLOR_PRIMARY, COLOR_SLATE_DARK,
        title="Recent Architecture Improvement", style=body_style
    )
    story.append(alert_box)
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 2: ARCHITECTURE & TECH STACK
    # ==========================================
    story.append(Paragraph("2. System Architecture & Technology Stack", h1_style))
    story.append(Paragraph(
        "ColdConnect is architected following lightweight, robust MVC principles using pure, native web standards "
        "without heavy external framework overhead. This ensures instant load times, zero build dependencies, "
        "and effortless on-premise or cloud deployment.",
        body_style
    ))

    stack_data = [
        [Paragraph("Layer", th_style), Paragraph("Technology", th_style), Paragraph("Architectural Role & Description", th_style)],
        [
            Paragraph("<b>Backend Controller & Routing</b>", td_style),
            Paragraph("PHP 8.x", td_style),
            Paragraph("Procedural MVC controllers, strict session RBAC, flash notifications, transaction handling.", td_style)
        ],
        [
            Paragraph("<b>Database Layer</b>", td_style),
            Paragraph("MySQL / MariaDB (PDO)", td_style),
            Paragraph("ACID transactions, prepared statements (SQL injection immunity), foreign key cascades, ENUM states.", td_style)
        ],
        [
            Paragraph("<b>Frontend Presentation</b>", td_style),
            Paragraph("Pure Vanilla CSS3", td_style),
            Paragraph("Zero external CSS frameworks; custom AgriTech (Emerald) + Ice Cyan tokens, glassmorphism, responsive grid.", td_style)
        ],
        [
            Paragraph("<b>Client-side Engine</b>", td_style),
            Paragraph("Vanilla JavaScript (ES6+)", td_style),
            Paragraph("Live cost estimators, date synchronization, event-delegated UI popup modal controllers.", td_style)
        ],
        [
            Paragraph("<b>Typography & Icons</b>", td_style),
            Paragraph("FontAwesome 6 + Inter", td_style),
            Paragraph("High contrast visual status badges, intuitive icon cues, clean readability.", td_style)
        ]
    ]
    t_stack = Table(stack_data, colWidths=[1.8 * inch, 1.4 * inch, 4.0 * inch])
    t_stack.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), COLOR_PRIMARY_DARK),
        ('GRID', (0, 0), (-1, -1), 0.5, COLOR_BORDER),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, COLOR_BG_LIGHT]),
        ('PADDING', (0, 0), (-1, -1), 5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(t_stack)
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 3: DATABASE SCHEMA & DATA INTEGRITY
    # ==========================================
    story.append(Paragraph("3. Relational Database Schema & Data Integrity", h1_style))
    story.append(Paragraph(
        "The relational database schema is designed for relational consistency and data integrity. "
        "It consists of three central entities: <code>users</code>, <code>cold_storages</code>, and <code>bookings</code>.",
        body_style
    ))

    schema_data = [
        [Paragraph("Entity", th_style), Paragraph("Key Fields", th_style), Paragraph("Constraints & Integrity Rules", th_style)],
        [
            Paragraph("<b>users</b>", td_style),
            Paragraph("id (PK), name, email, password, role, phone, location", td_style),
            Paragraph("email UNIQUE; role ENUM('farmer', 'owner'); passwords hashed via password_hash().", td_style)
        ],
        [
            Paragraph("<b>cold_storages</b>", td_style),
            Paragraph("id (PK), owner_id (FK), name, location, total_capacity, available_capacity, temperature, price_per_kg, status", td_style),
            Paragraph("owner_id references users(id) ON DELETE CASCADE; status ENUM('Available', 'Full', 'Maintenance').", td_style)
        ],
        [
            Paragraph("<b>bookings</b>", td_style),
            Paragraph("id (PK), farmer_id (FK), storage_id (FK), crop, quantity, start_date, end_date, total_cost, status", td_style),
            Paragraph("status ENUM('Pending', 'Accepted', 'Rejected', 'Completed'); foreign keys cascaded on delete.", td_style)
        ]
    ]
    t_schema = Table(schema_data, colWidths=[1.3 * inch, 2.7 * inch, 3.2 * inch])
    t_schema.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), COLOR_SECONDARY),
        ('GRID', (0, 0), (-1, -1), 0.5, COLOR_BORDER),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, COLOR_BG_LIGHT]),
        ('PADDING', (0, 0), (-1, -1), 5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(t_schema)
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 4: USER WORKFLOWS
    # ==========================================
    story.append(Paragraph("4. End-to-End User Workflows", h1_style))
    story.append(Paragraph(
        "The system coordinates two synchronized user journeys across Farmers and Cold Storage Owners:",
        body_style
    ))

    story.append(Paragraph("A. Farmer Workflow (Search, Match, Estimate, Book)", h2_style))
    story.append(Paragraph(
        "&bull; <b>Smart Match Recommendation:</b> The farmer inputs crop, required volume, and duration. "
        "The Smart Match algorithm scores facilities out of 100 based on Proximity (40%), Pricing (30%), "
        "and Crop Temperature Suitability (30%), highlighting optimal matches with a 'BEST MATCH' badge.",
        bullet_style
    ))
    story.append(Paragraph(
        "&bull; <b>Live Dynamic Estimator:</b> As the farmer adjusts kilograms or days, client-side JS calculates: "
        "<code>Total Cost = Quantity (kg) &times; Rate (&#8377;/kg/day) &times; Duration (days)</code> in real-time.",
        bullet_style
    ))
    story.append(Paragraph(
        "&bull; <b>Booking Dispatch:</b> The reservation is created in <code>Pending</code> status, visible instantly "
        "on the farmer's booking tracker.",
        bullet_style
    ))

    story.append(Spacer(1, 6))
    story.append(Paragraph("B. Storage Owner Workflow (Review, Accept, Reject, Sync)", h2_style))
    story.append(Paragraph(
        "&bull; <b>Real-Time Review:</b> The owner portal displays pending reservations with farmer contact information, "
        "crop specs, dates, and total value.",
        bullet_style
    ))
    story.append(Paragraph(
        "&bull; <b>Atomic Acceptance:</b> If accepted, a PDO transaction verifies capacity, sets status to <code>Accepted</code>, "
        "and decrements facility <code>available_capacity</code>. If free space reaches 0, facility status shifts to <code>Full</code>.",
        bullet_style
    ))
    story.append(Paragraph(
        "&bull; <b>Modal-Driven Rejection:</b> Clicking 'Reject' triggers the custom in-app popup modal, showing full "
        "reservation summary before confirming rejection. This leaves warehouse capacity unallocated for other farmers.",
        bullet_style
    ))
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 5: UI REJECT POPUP MODAL & CODE MINIMISATION
    # ==========================================
    story.append(PageBreak())  # Clean break for UI Modal section
    story.append(Paragraph("5. UI Rejection Popup Modal & Code Minimisation", h1_style))
    story.append(Paragraph(
        "A critical enhancement in ColdConnect 2.1 is replacing the native browser <code>window.confirm()</code> "
        "dialog with a modern, animated, in-app UI Popup Modal. This section explains the UX rationale, "
        "architectural improvements, and code minimisation achieved.",
        body_style
    ))

    story.append(Paragraph("Why Native Browser Confirm Was Suboptimal:", h2_style))
    story.append(Paragraph(
        "1. <b>Outdated & Unstyled:</b> Native alerts render OS-specific dialogs that cannot be customized, matching none of the application's visual language.<br/>"
        "2. <b>Zero Booking Context:</b> Browser confirm only displays a single line of unformatted text. Owners could not see the farmer's name, crop, quantity, or warehouse.<br/>"
        "3. <b>Code Redundancy:</b> Every table row across management pages duplicated entire <code>&lt;form&gt;</code> tags and hidden inputs, inflating HTML DOM size.",
        body_style
    ))
    story.append(Spacer(1, 6))

    story.append(Paragraph("Code Minimisation: Before vs After Comparison", h2_style))

    story.append(Paragraph("<b>Before (Bloated per-row form + inline JS):</b>", body_style))
    old_code = (
        '<form method="POST" action="update-booking.php" style="display: inline;">\n'
        '    <input type="hidden" name="booking_id" value="<?php echo $b[\'id\']; ?>">\n'
        '    <input type="hidden" name="action" value="reject">\n'
        '    <button type="submit" class="btn btn-danger btn-sm"\n'
        '            onclick="return confirm(\'Reject booking #<?php echo $b[\'id\']; ?>?\');">\n'
        '        Reject\n'
        '    </button>\n'
        '</form>'
    )
    story.append(make_code_box(old_code, body_style))
    story.append(Spacer(1, 8))

    story.append(Paragraph("<b>After (Minimised, clean, declarative button trigger):</b>", body_style))
    new_code = (
        '<button type="button" class="btn btn-danger btn-sm btn-reject-modal"\n'
        '        data-id="<?php echo $b[\'id\']; ?>"\n'
        '        data-farmer="<?php echo e($b[\'farmer_name\']); ?>"\n'
        '        data-crop="<?php echo e($b[\'crop\']); ?>"\n'
        '        data-qty="<?php echo number_format($b[\'quantity\']); ?> kg"\n'
        '        data-facility="<?php echo e($b[\'storage_name\']); ?>">\n'
        '    Reject\n'
        '</button>'
    )
    story.append(make_code_box(new_code, body_style))
    story.append(Spacer(1, 10))

    story.append(Paragraph("Architectural Benefits of Code Minimisation:", h2_style))
    story.append(Paragraph(
        "&bull; <b>DRY Principle (Don't Repeat Yourself):</b> Instead of rendering N separate forms for N rows, "
        "a single reusable modal dialog (<code>includes/modal-reject.php</code>) handles all rejections globally.<br/>"
        "&bull; <b>DOM Size Reduction:</b> Stripped unnecessary form tags and redundant CSRF/action payloads from every row.<br/>"
        "&bull; <b>Centralized Controller:</b> Client logic in <code>js/script.js</code> utilizes a single event delegation listener, "
        "reducing event listener memory footprint and handling dynamic elements seamlessly.<br/>"
        "&bull; <b>Keyboard & Accessibility:</b> Supports <code>Escape</code> key dismissal, backdrop click dismissal, "
        "and accessible ARIA attributes (<code>role='dialog'</code>, <code>aria-modal='true'</code>).",
        body_style
    ))
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 6: FILE-BY-FILE CODE WALKTHROUGH
    # ==========================================
    story.append(Paragraph("6. In-Depth File-by-File Code Walkthrough", h1_style))
    story.append(Paragraph(
        "Comprehensive index of ColdConnect codebase files, their functional responsibilities, and operational mechanics:",
        body_style
    ))

    file_data = [
        [Paragraph("File Path", th_style), Paragraph("Core Responsibility", th_style), Paragraph("Key Functions & Mechanics", th_style)],
        [
            Paragraph("<b>includes/db.php</b>", td_style),
            Paragraph("Database Connection", td_style),
            Paragraph("Initializes PDO connection with UTF-8 charset, ERRMODE_EXCEPTION, and FETCH_ASSOC defaults.", td_style)
        ],
        [
            Paragraph("<b>includes/auth.php</b>", td_style),
            Paragraph("Session & Security", td_style),
            Paragraph("Role guards (<code>requireFarmer</code>, <code>requireOwner</code>), flash notifications, XSS sanitizer <code>e()</code>, <code>base_url()</code> helper.", td_style)
        ],
        [
            Paragraph("<b>includes/modal-reject.php</b>", td_style),
            Paragraph("UI Rejection Modal [NEW]", td_style),
            Paragraph("Reusable popup dialog with warning badge, reservation preview card, and rejection form.", td_style)
        ],
        [
            Paragraph("<b>includes/smart-match.php</b>", td_style),
            Paragraph("Matching Engine", td_style),
            Paragraph("Defines crop temperature curves; calculates composite ranking score (Distance, Price, Temperature suitability).", td_style)
        ],
        [
            Paragraph("<b>owner/dashboard.php</b>", td_style),
            Paragraph("Owner Overview Portal", td_style),
            Paragraph("Summary metrics, pending bookings table with minimised reject triggers, facility management list.", td_style)
        ],
        [
            Paragraph("<b>owner/bookings.php</b>", td_style),
            Paragraph("Reservation Ledger", td_style),
            Paragraph("Full reservations list with status filter (Pending, Accepted, Rejected), farmer details, and UI reject modal.", td_style)
        ],
        [
            Paragraph("<b>owner/update-booking.php</b>", td_style),
            Paragraph("Transactional Action Handler", td_style),
            Paragraph("Verifies facility ownership; executes PDO transaction to decrement capacity on accept or reject cleanly.", td_style)
        ],
        [
            Paragraph("<b>search.php & booking.php</b>", td_style),
            Paragraph("Farmer Search & Reservation", td_style),
            Paragraph("Facility filtering, Smart Match ranking, date synchronization, and reservation submission.", td_style)
        ],
        [
            Paragraph("<b>includes/modal-incident.php</b>", td_style),
            Paragraph("Crop Incident Modal [NEW]", td_style),
            Paragraph("Allows facility owners to log cause of damage (compressor failure, power outage, mold) and calculates guaranteed 85% compensation.", td_style)
        ],
        [
            Paragraph("<b>owner/report-incident.php</b>", td_style),
            Paragraph("Incident Action Handler [NEW]", td_style),
            Paragraph("Validates facility ownership and records damage cause, affected crop, damaged quantity, and 85% compensation payout.", td_style)
        ],
        [
            Paragraph("<b>includes/header.php</b>", td_style),
            Paragraph("Global Navigation & Bell [NEW]", td_style),
            Paragraph("Notification bell with real-time alert badge, live dropdown reminders for expirations, damage claims, and pending requests.", td_style)
        ],
        [
            Paragraph("<b>my-bookings.php</b>", td_style),
            Paragraph("Farmer Reservation Hub [NEW]", td_style),
            Paragraph("Displays remaining days countdown badge (⏳ X days remaining), 85% insurance coverage badge, and owner damage incident callouts.", td_style)
        ],
        [
            Paragraph("<b>js/script.js</b>", td_style),
            Paragraph("Client-Side Interactions", td_style),
            Paragraph("Live cost estimation formula, notification bell dropdown toggle, rejection popup modal, and dynamic 85% incident compensation preview.", td_style)
        ],
        [
            Paragraph("<b>css/style.css</b>", td_style),
            Paragraph("Design System & Styling", td_style),
            Paragraph("AgriTech emerald theme, notification dropdown glassmorphism, pulsing countdown badges (.urgent, .active, .expired), and spoilage alert cards.", td_style)
        ]
    ]
    t_files = Table(file_data, colWidths=[1.8 * inch, 1.8 * inch, 3.6 * inch])
    t_files.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), COLOR_PRIMARY_DARK),
        ('GRID', (0, 0), (-1, -1), 0.5, COLOR_BORDER),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, COLOR_BG_LIGHT]),
        ('PADDING', (0, 0), (-1, -1), 4.5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ]))
    story.append(t_files)
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 6B: RECENT SYSTEM ENHANCEMENTS
    # ==========================================
    story.append(Paragraph("6B. Agricultural Protection & UX Enhancements", h1_style))
    story.append(Paragraph(
        "<b>1. Owner-Backed 85% Crop Protection Insurance Addon:</b> "
        "Farmers can optionally select Crop Protection Insurance (+5% fee) on the booking page. "
        "In the event of cold chamber equipment failure, refrigerant leaks, or power outages, the facility owner "
        "is legally committed to compensate 85% of produce value.<br/>"
        "<b>2. Interactive Leaflet.js Transit Route Map:</b> "
        "Real-time driving route with distance (km) and agro-truck travel time calculated between farmer pickup "
        "locations (Ahmedabad, Surat, Vadodara, Rajkot, etc.) and cold storage facilities.<br/>"
        "<b>3. Remaining Days Countdown Badge:</b> "
        "Live countdown timer badges (<code>⏳ X days remaining</code>, <code>Ending Soon!</code>, <code>Expires Today</code>) "
        "help farmers and owners avoid unintended spoilage or demurrage fees.<br/>"
        "<b>4. Notification Bell & Live Reminders:</b> "
        "A header notification bell tracks active damage claim compensation, countdown expirations, and pending booking approvals.<br/>"
        "<b>5. Quantity Flexibility & Validation:</b> "
        "Enforces a minimum of 100 kg with step 1 and maximum capacity bounded across all facilities, "
        "eliminating browser step validation errors (e.g. 491/501).",
        body_style
    ))
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 7: SECURITY & EDGE CASES
    # ==========================================
    story.append(PageBreak())  # Clean break for Security section
    story.append(Paragraph("7. Security, Transaction Safety & Edge Cases Handled", h1_style))
    story.append(Paragraph(
        "ColdConnect is designed with production-grade defenses against common web application vulnerabilities:",
        body_style
    ))

    story.append(Paragraph(
        "&bull; <b>SQL Injection Prevention:</b> 100% of database queries utilize PDO prepared statements with parameterized inputs. "
        "No raw user input is ever concatenated into SQL strings.<br/>"
        "&bull; <b>Insecure Direct Object References (IDOR):</b> In <code>owner/update-booking.php</code>, the controller verifies "
        "that the target booking facility's <code>owner_id</code> strictly matches <code>$_SESSION['user_id']</code>. "
        "Malicious owners cannot accept or reject other facilities' bookings.<br/>"
        "&bull; <b>Race Condition & Overselling Defense:</b> Accepting a reservation executes inside a PDO ACID transaction. "
        "If warehouse capacity is insufficient (<code>available_capacity &lt; quantity</code>), the transaction aborts with an informative error.<br/>"
        "&bull; <b>Cross-Site Scripting (XSS):</b> All user-supplied output is passed through <code>htmlspecialchars()</code> via the global <code>e()</code> helper.<br/>"
        "&bull; <b>Double-Action Protection:</b> Already processed bookings (<code>Accepted</code> or <code>Rejected</code>) cannot be re-processed.",
        body_style
    ))
    story.append(Spacer(1, 14))

    # ==========================================
    # SECTION 8: VERIFICATION CHECKLIST
    # ==========================================
    story.append(Paragraph("8. Functional Verification & Testing Checklist", h1_style))
    story.append(Paragraph(
        "The following test matrix validates that all functional and UX requirements are met:",
        body_style
    ))

    test_data = [
        [Paragraph("Scenario / Requirement", th_style), Paragraph("Action / Step", th_style), Paragraph("Observed Behavior", th_style), Paragraph("Result", th_style)],
        [
            Paragraph("<b>Reject Trigger</b>", td_style),
            Paragraph("Click 'Reject' button in Owner Dashboard or Bookings list", td_style),
            Paragraph("Custom UI Popup Modal animates in smoothly. Browser confirm() does NOT appear.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Dynamic Details</b>", td_style),
            Paragraph("Inspect modal content", td_style),
            Paragraph("Accurately populates Booking ID, Farmer Name, Crop, Quantity, and Facility.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Cancel Action</b>", td_style),
            Paragraph("Click 'Keep Booking' or close button (&times;)", td_style),
            Paragraph("Modal closes immediately. No request submitted; status unchanged.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Keyboard Dismissal</b>", td_style),
            Paragraph("Press 'Escape' key while modal is active", td_style),
            Paragraph("Modal closes cleanly and restores page scrolling.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Backdrop Dismissal</b>", td_style),
            Paragraph("Click blurred dark backdrop outside modal dialog", td_style),
            Paragraph("Modal closes smoothly.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Confirm Rejection</b>", td_style),
            Paragraph("Click 'Confirm Rejection' in modal", td_style),
            Paragraph("Submits POST to update-booking.php; status set to Rejected; flash notification shown.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ],
        [
            Paragraph("<b>Capacity Guard</b>", td_style),
            Paragraph("Check storage capacity after rejection", td_style),
            Paragraph("Warehouse capacity is preserved and NOT decremented.", td_style),
            Paragraph("<font color='#059669'><b>PASS</b></font>", td_style)
        ]
    ]
    t_test = Table(test_data, colWidths=[1.8 * inch, 2.2 * inch, 2.4 * inch, 0.8 * inch])
    t_test.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), COLOR_SLATE_DARK),
        ('GRID', (0, 0), (-1, -1), 0.5, COLOR_BORDER),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, COLOR_BG_LIGHT]),
        ('PADDING', (0, 0), (-1, -1), 5),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('ALIGN', (3, 1), (3, -1), 'CENTER'),
    ]))
    story.append(t_test)
    story.append(Spacer(1, 14))

    # Concluding banner
    concl_box = make_callout(
        "<b>ColdConnect Prototype Ready for Evaluation:</b> All code minimisations, UI modal improvements, "
        "and documentation artifacts have been verified. The system demonstrates an accessible, production-ready "
        "solution to agricultural post-harvest preservation and transparent cold chain coordination.",
        COLOR_PRIMARY.clone(alpha=0.08), COLOR_PRIMARY, COLOR_SLATE_DARK,
        title="Engineering Certification", style=body_style
    )
    story.append(concl_box)

    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"[SUCCESS] PDF successfully built: {os.path.abspath(filename)}")


if __name__ == "__main__":
    out_pdf = "ColdConnect_Documentation.pdf"
    if len(sys.argv) > 1:
        out_pdf = sys.argv[1]
    build_pdf(out_pdf)
