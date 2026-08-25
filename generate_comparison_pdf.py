#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm, cm
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY, TA_RIGHT
from reportlab.platypus import (SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
                                PageBreak, HRFlowable, KeepTogether, Image)
from reportlab.lib import colors
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import datetime

# Colors
AVAZONIA_RED = HexColor("#E5001A")
AVAZONIA_RED_DARK = HexColor("#B80015")
AVAZONIA_BLACK = HexColor("#111111")
DARK_GRAY = HexColor("#2B2B2B")
MID_GRAY = HexColor("#6B7280")
LIGHT_GRAY = HexColor("#F3F4F6")
LIGHT_BG = HexColor("#F9FAFB")
GREEN = HexColor("#0A7F2E")  # Jiji
ORANGE = HexColor("#F27D26") # Jumia
BLUE = HexColor("#2563EB")
LIGHT_RED_BG = HexColor("#FFF1F2")
LIGHT_GREEN_BG = HexColor("#F0FDF4")
LIGHT_ORANGE_BG = HexColor("#FFF7ED")

OUTPUT = r"C:\Users\ABCD\Desktop\Avazonia\docs\Avazonia_Jiji_Jumia_Comparison.pdf"

styles = getSampleStyleSheet()

sTitle = ParagraphStyle('TitleCustom', parent=styles['Title'], fontSize=32, leading=36, textColor=AVAZONIA_RED, alignment=TA_CENTER, spaceAfter=8, fontName='Helvetica-Bold')
sSubtitle = ParagraphStyle('Subtitle', parent=styles['Normal'], fontSize=11, leading=15, textColor=MID_GRAY, alignment=TA_CENTER, fontName='Helvetica')
sH1 = ParagraphStyle('H1', parent=styles['Heading1'], fontSize=18, leading=22, textColor=AVAZONIA_RED, fontName='Helvetica-Bold', spaceBefore=6, spaceAfter=8, keepWithNext=True)
sH2 = ParagraphStyle('H2', parent=styles['Heading2'], fontSize=13, leading=16, textColor=DARK_GRAY, fontName='Helvetica-Bold', spaceBefore=10, spaceAfter=6, keepWithNext=True)
sH3 = ParagraphStyle('H3', parent=styles['Heading3'], fontSize=11, leading=14, textColor=DARK_GRAY, fontName='Helvetica-Bold', spaceBefore=8, spaceAfter=4)
sBody = ParagraphStyle('Body', parent=styles['Normal'], fontSize=9.5, leading=14, textColor=HexColor("#374151"), fontName='Helvetica', spaceAfter=6, alignment=TA_JUSTIFY)
sBodySmall = ParagraphStyle('BodySmall', parent=sBody, fontSize=8.5, leading=12, spaceAfter=4)
sBullet = ParagraphStyle('Bullet', parent=sBody, leftIndent=14, bulletIndent=6, spaceAfter=3, alignment=TA_LEFT)
sCaption = ParagraphStyle('Caption', parent=styles['Normal'], fontSize=7.5, leading=10, textColor=MID_GRAY, alignment=TA_CENTER, fontName='Helvetica-Oblique', spaceBefore=4)
sTableCell = ParagraphStyle('TableCell', parent=styles['Normal'], fontSize=8.5, leading=11, textColor=HexColor("#111827"), fontName='Helvetica', alignment=TA_LEFT)
sTableCellCenter = ParagraphStyle('TableCellCenter', parent=sTableCell, alignment=TA_CENTER)
sTableHeader = ParagraphStyle('TableHeader', parent=sTableCell, fontName='Helvetica-Bold', textColor=white, alignment=TA_CENTER, fontSize=8)
sFooter = ParagraphStyle('Footer', parent=styles['Normal'], fontSize=7, leading=9, textColor=MID_GRAY, alignment=TA_CENTER, fontName='Helvetica')

def header_footer(canvas, doc):
    canvas.saveState()
    # Footer line
    canvas.setStrokeColor(HexColor("#E5E7EB"))
    canvas.setLineWidth(0.5)
    canvas.line(20*mm, 14*mm, A4[0]-20*mm, 14*mm)
    canvas.setFont("Helvetica", 7)
    canvas.setFillColor(MID_GRAY)
    footer_text = "Avazonia - Confidential - Strategic Comparison: Jiji vs Jumia - August 2026"
    canvas.drawCentredString(A4[0]/2, 10*mm, footer_text)
    # Page number
    canvas.setFont("Helvetica", 7)
    canvas.drawRightString(A4[0]-15*mm, 10*mm, f"{doc.page}")
    # Top small brand on inner pages (not cover)
    if doc.page > 1:
        canvas.setFont("Helvetica-Bold", 7)
        canvas.setFillColor(AVAZONIA_RED)
        canvas.drawString(15*mm, A4[1]-10*mm, "AVAZONIA")
        canvas.setFont("Helvetica", 6)
        canvas.setFillColor(MID_GRAY)
        canvas.drawRightString(A4[0]-15*mm, A4[1]-10*mm, "Jiji vs Jumia  -  What It Means For Us")
    canvas.restoreState()

def cover_header_footer(canvas, doc):
    # Only footer, no top brand
    canvas.saveState()
    canvas.setStrokeColor(HexColor("#E5E7EB"))
    canvas.setLineWidth(0.5)
    canvas.line(20*mm, 14*mm, A4[0]-20*mm, 14*mm)
    canvas.setFont("Helvetica", 7)
    canvas.setFillColor(MID_GRAY)
    canvas.drawCentredString(A4[0]/2, 10*mm, "Avazonia - Confidential - August 2026")
    canvas.restoreState()

# Build story
story = []

# COVER PAGE
story.append(Spacer(1, 28*mm))
# Red pill
pill_data = [[Paragraph('<font color="#FFFFFF" size="7"><b>STRATEGIC RESEARCH</b></font>', sCaption)]]
pill_table = Table(pill_data, colWidths=[45*mm])
pill_table.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), AVAZONIA_RED),
    ('ROUNDEDCORNERS', [6,6,6,6]),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 4),
    ('BOTTOMPADDING', (0,0), (-1,-1), 4),
    ('ALIGN', (0,0), (-1,-1), 'CENTER'),
]))
story.append(pill_table)
story.append(Spacer(1, 10*mm))
story.append(Paragraph("Jiji vs Jumia", ParagraphStyle('CoverSmall', parent=sTitle, fontSize=18, textColor=MID_GRAY, fontName='Helvetica', alignment=TA_CENTER, spaceAfter=2)))
story.append(Paragraph("What It Means For", ParagraphStyle('CoverMid', parent=sTitle, fontSize=26, textColor=DARK_GRAY, alignment=TA_CENTER, spaceAfter=0)))
story.append(Paragraph("AVAZONIA", ParagraphStyle('CoverBig', parent=sTitle, fontSize=38, leading=38, textColor=AVAZONIA_RED, alignment=TA_CENTER, spaceAfter=6, fontName='Helvetica-Bold')))
story.append(Spacer(1, 4*mm))
story.append(HRFlowable(width="20%", thickness=1.2, color=AVAZONIA_RED, spaceBefore=0, spaceAfter=6*mm, hAlign='CENTER', vAlign='BOTTOM'))
story.append(Paragraph("A simple, non-technical guide for decision makers.<br/>How Ghanas two biggest marketplaces work - and how<br/>Avazonia can take the best of both without the mistakes.", ParagraphStyle('CoverDesc', parent=sSubtitle, fontSize=10, leading=15, alignment=TA_CENTER)))
story.append(Spacer(1, 14*mm))
# Info box
info_data = [
    [Paragraph('<b>Prepared for</b><br/>Avazonia Management', ParagraphStyle('InfoLeft', parent=sBodySmall, alignment=TA_LEFT, fontSize=8, leading=11)),
     Paragraph('<b>Date</b><br/>August 2026', ParagraphStyle('InfoCenter', parent=sBodySmall, alignment=TA_CENTER, fontSize=8, leading=11)),
     Paragraph('<b>Focus</b><br/>Ghana - Vendor / Seller Model', ParagraphStyle('InfoRight', parent=sBodySmall, alignment=TA_RIGHT, fontSize=8, leading=11))],
]
info_table = Table(info_data, colWidths=[55*mm, 35*mm, 55*mm])
info_table.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_BG),
    ('ROUNDEDCORNERS', [8,8,8,8]),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 8),
    ('BOTTOMPADDING', (0,0), (-1,-1), 8),
]))
story.append(info_table)
story.append(Spacer(1, 10*mm))
story.append(Paragraph("This is not a technical document. No jargon. Just how it works, how money is made,<br/>and what Avazonia should copy - and avoid.", ParagraphStyle('CoverNote', parent=sCaption, fontSize=7.5, textColor=MID_GRAY, alignment=TA_CENTER)))

# PAGE 2: Why this matters
story.append(PageBreak())
story.append(Paragraph("Why We Compared Jiji &amp; Jumia", sH1))
story.append(Paragraph("Avazonia is not building just an online shop. We are building a <b>mall</b> - a place where many different sellers can open shops, and Avazonia itself also has its own shop inside the same building.", sBody))
story.append(Spacer(1, 2*mm))
# Analogy box
analogy = [
    [Paragraph('<font color="#E5001A"><b>Think of it like a mall</b></font><br/><br/><b>Avazonia owns the mall</b> - the building, the security, the lights, the customers coming through the door.<br/><br/><b>Sellers rent space</b> - they bring their own products, set their own prices, and pay Avazonia a small fee when they make a sale.<br/><br/><b>Avazonia also sells</b> - we have our own shop inside our own mall. Customers see all products together and choose the best deal, whether it is from Avazonia or another seller.', sBodySmall)],
]
analogy_table = Table(analogy, colWidths=[170*mm])
analogy_table.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_RED_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FECDD3")),
    ('ROUNDEDCORNERS', [8,8,8,8]),
    ('LEFTPADDING', (0,0), (-1,-1), 10),
    ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ('TOPPADDING', (0,0), (-1,-1), 10),
    ('BOTTOMPADDING', (0,0), (-1,-1), 10),
]))
story.append(analogy_table)
story.append(Spacer(1, 4*mm))
story.append(Paragraph("Ghanas two biggest examples work very differently. One is like a <b>notice board</b> (Jiji). The other is like a <b>supermarket checkout</b> (Jumia). Avazonia needs to be both - intelligently.", sBody))
story.append(Paragraph("We studied both in Ghana to answer one question: <b>How should Avazonia handle sellers, money, and delivery - without making the mistakes they made?</b>", sBody))
story.append(Spacer(1, 3*mm))
# Key question box
q_data = [[
    Paragraph('<b><font color="#111827">The key question for Avazonia</font></b><br/><font color="#6B7280" size="8">Should Avazonia touch the money and the delivery, or just connect buyer and seller?<br/>Answer: <b>Both - but for different products.</b></font>', sBodySmall)
]]
q_table = Table(q_data, colWidths=[170*mm])
q_table.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), HexColor("#FFFBEB")),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FDE68A")),
    ('ROUNDEDCORNERS', [8,8,8,8]),
    ('LEFTPADDING', (0,0), (-1,-1), 10),
    ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ('TOPPADDING', (0,0), (-1,-1), 10),
    ('BOTTOMPADDING', (0,0), (-1,-1), 10),
]))
story.append(q_table)

# PAGE 3: How Jiji Works
story.append(Paragraph("How <font color=\"#0A7F2E\">Jiji</font> Works", ParagraphStyle('H1Green', parent=sH1, textColor=GREEN)))
story.append(Paragraph("<b>In one sentence:</b> Jiji is a free notice board. Anyone can pin their item for free. When someone is interested, they call the seller directly. Jiji does not handle money or delivery.", sBody))
story.append(Spacer(1, 2*mm))
# Steps
steps_jiji = [
    [Paragraph('<b><font color="#0A7F2E">1. Pin it</font></b>', sTableCellCenter), Paragraph('<b>Seller posts an ad</b> - photos, price, category. Free. Takes 3 minutes. No shop needed.', sTableCell), Paragraph('<font color="#6B7280" size="7">Example: "Toyota Corolla 2010 - GHS 45,000 - Accra"</font>', sBodySmall)],
    [Paragraph('<b><font color="#0A7F2E">2. Wait</font></b>', sTableCellCenter), Paragraph('<b>Buyer finds and calls</b> - via phone or chat inside Jiji. They negotiate price directly.', sTableCell), Paragraph('<font color="#6B7280" size="7">No checkout button. Just "Call" or "Chat"</font>', sBodySmall)],
    [Paragraph('<b><font color="#0A7F2E">3. Meet</font></b>', sTableCellCenter), Paragraph('<b>They meet or arrange delivery themselves</b> - Jiji does not deliver. No guarantee.', sTableCell), Paragraph('<font color="#6B7280" size="7">Meet in public place, pay on inspection</font>', sBodySmall)],
    [Paragraph('<b><font color="#0A7F2E">4. Promote (optional)</font></b>', sTableCellCenter), Paragraph('<b>Seller pays to be seen more</b> - "Boost" puts ad at top for 3 days. From GHS 20.', sTableCell), Paragraph('<font color="#6B7280" size="7">Free = invisible. Paid = seen 100x more</font>', sBodySmall)],
]
t_jiji = Table(steps_jiji, colWidths=[28*mm, 82*mm, 60*mm])
t_jiji.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (0,-1), LIGHT_GREEN_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#BBF7D0")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_jiji)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("How does Jiji make money if listing is free?", sH2))
money_jiji = [
    [Paragraph('<b>Boost / Top Ad</b><br/><font size="7" color="#6B7280">Pay to appear at top<br/>GHS 20 - 200</font>', sTableCellCenter), Paragraph('<b>VIP Subscription</b><br/><font size="7" color="#6B7280">Monthly fee for big dealers<br/>Up to $1,200/mo</font>', sTableCellCenter), Paragraph('<b>Banner Ads</b><br/><font size="7" color="#6B7280">Samsung/Toyota pay to advertise</font>', sTableCellCenter), Paragraph('<b>New: Lead Fees</b><br/><font size="7" color="#6B7280">$12-45 per serious buyer (cars/houses)</font>', sTableCellCenter)],
]
t_money_jiji = Table(money_jiji, colWidths=[42.5*mm, 42.5*mm, 42.5*mm, 42.5*mm])
t_money_jiji.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_GREEN_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#BBF7D0")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_money_jiji)
story.append(Spacer(1, 3*mm))
# Pros cons
pros_cons_jiji = [
    [Paragraph('<b><font color="#0A7F2E">OK What works</font></b>', sTableCellCenter), Paragraph('<b><font color="#B91C1C">X What does not</font></b>', sTableCellCenter)],
    [Paragraph('- Anyone can start selling in 3 minutes<br/>- Zero fees - great for small sellers<br/>- 45M visits/month, 85% on phone - huge audience<br/>- Good for cars, houses, used items', sBodySmall), Paragraph('- No payment protection - scams happen<br/>- No delivery - seller must handle it<br/>- No guarantee - buyer may not show up<br/>- If you do not pay to boost, nobody sees you', sBodySmall)],
]
t_pc_jiji = Table(pros_cons_jiji, colWidths=[85*mm, 85*mm])
t_pc_jiji.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,0), LIGHT_GREEN_BG),
    ('BACKGROUND', (0,1), (0,1), HexColor("#F0FDF4")),
    ('BACKGROUND', (1,1), (1,1), HexColor("#FEF2F2")),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_pc_jiji)
story.append(Paragraph("Bottom line: Jiji is cheap to start, but <b>Avazonia cannot rely on this alone</b> - we need to handle money and delivery for most products, or customers will not trust us.", ParagraphStyle('Note', parent=sBodySmall, textColor=MID_GRAY, fontName='Helvetica-Oblique', alignment=TA_LEFT, leftIndent=0)))

# PAGE 4: How Jumia Works
story.append(Paragraph("How <font color=\"#F27D26\">Jumia</font> Works", ParagraphStyle('H1Orange', parent=sH1, textColor=ORANGE)))
story.append(Paragraph("<b>In one sentence:</b> Jumia is a supermarket checkout. Seller puts product on shelf, buyer pays Jumia, Jumia delivers, then Jumia pays seller (minus a small fee). Everything is tracked.", sBody))
steps_jumia = [
    [Paragraph('<b><font color="#F27D26">1. Register</font></b>', sTableCellCenter), Paragraph('<b>Seller registers</b> - Ghana Card or business papers + bank account. 5 minutes. Free. Must do training.', sTableCell), Paragraph('<font color="#6B7280" size="7">Verified - not anyone can sell</font>', sBodySmall)],
    [Paragraph('<b><font color="#F27D26">2. List</font></b>', sTableCellCenter), Paragraph('<b>Upload products</b> - photos, price, stock. Jumia checks quality before it goes live.', sTableCell), Paragraph('<font color="#6B7280" size="7">Bad photos = rejected</font>', sBodySmall)],
    [Paragraph('<b><font color="#F27D26">3. Sell</font></b>', sTableCellCenter), Paragraph('<b>Buyer pays on Jumia</b> - card, mobile money, or cash on delivery. Money goes to Jumia first.', sTableCell), Paragraph('<font color="#6B7280" size="7">Seller does not touch money yet</font>', sBodySmall)],
    [Paragraph('<b><font color="#F27D26">4. Deliver & Get Paid</font></b>', sTableCellCenter), Paragraph('<b>Jumia delivers & pays seller weekly</b> - after deducting fee. Seller sees statement.', sTableCell), Paragraph('<font color="#6B7280" size="7">Payout every 7 days to bank</font>', sBodySmall)],
]
t_jumia = Table(steps_jumia, colWidths=[28*mm, 82*mm, 60*mm])
t_jumia.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (0,-1), LIGHT_ORANGE_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FED7AA")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_jumia)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("How does Jumia make money?", sH2))
money_jumia = [
    [Paragraph('<b>Commission</b><br/><font size="7" color="#6B7280">5% - 20% of sale price<br/>By category</font>', sTableCellCenter), Paragraph('<b>Example</b><br/><font size="7" color="#6B7280">Fashion 20%: You want GHS 950<br/>Buyer pays GHS 1,198<br/>Jumia keeps GHS 248</font>', sTableCellCenter), Paragraph('<b>Other fees</b><br/><font size="7" color="#6B7280">Storage, ads (Sponsored Products)<br/>Penalties for late/cancelled orders</font>', sTableCellCenter)],
]
t_money_jumia = Table(money_jumia, colWidths=[56*mm, 57*mm, 57*mm])
t_money_jumia.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_ORANGE_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FED7AA")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_money_jumia)
story.append(Spacer(1, 1*mm))
story.append(Paragraph("Categories: Electronics 5-8% - Fashion 15-20% - Home 10% (Ghana 2026, VAT included). No monthly fee. You only pay when you sell.", ParagraphStyle('SmallNote', parent=sCaption, alignment=TA_LEFT, leftIndent=2)))
story.append(Spacer(1, 2*mm))
# Delivery options
delivery = [
    [Paragraph('<b>Dropship</b><br/><font size="7" color="#374151">You keep stock<br/>You bring package to Jumia hub when order comes</font>', sTableCellCenter), Paragraph('<b>Jumia Express</b><br/><font size="7" color="#374151">Jumia keeps stock in their warehouse<br/>They pack & deliver for you</font>', sTableCellCenter)],
]
t_del = Table(delivery, colWidths=[85*mm, 85*mm])
t_del.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_ORANGE_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FED7AA")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_del)
story.append(Spacer(1, 3*mm))
pros_cons_jumia = [
    [Paragraph('<b><font color="#0A7F2E">OK What works</font></b>', sTableCellCenter), Paragraph('<b><font color="#B91C1C">X What does not</font></b>', sTableCellCenter)],
    [Paragraph('- Buyer trusts - money protected<br/>- Delivery handled (1,300+ hubs)<br/>- Weekly payout with statement<br/>- Training + support', sBodySmall), Paragraph('- Commission on every sale<br/>- Strict rules - late = penalty<br/>- Must keep stock accurate or get delisted<br/>- Small sellers struggle with fees', sBodySmall)],
]
t_pc_jumia = Table(pros_cons_jumia, colWidths=[85*mm, 85*mm])
t_pc_jumia.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,0), LIGHT_ORANGE_BG),
    ('BACKGROUND', (0,1), (0,1), HexColor("#F0FDF4")),
    ('BACKGROUND', (1,1), (1,1), HexColor("#FEF2F2")),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_pc_jumia)

# PAGE 5: Comparison Table
story.append(Paragraph("Side-by-Side: The Simple Difference", sH1))
story.append(Paragraph("Use this table when someone asks: <b>'Are we like Jiji or Jumia?'</b> - Answer: <b>Both, on purpose.</b>", sBody))
# Comparison table
header = [
    Paragraph('<b>Feature</b>', sTableHeader),
    Paragraph('<b>Jiji</b><br/><font size="6">Notice Board</font>', sTableHeader),
    Paragraph('<b>Jumia</b><br/><font size="6">Supermarket Checkout</font>', sTableHeader),
    Paragraph('<b>Avazonia</b><br/><font size="6">Best of Both</font>', sTableHeader),
]
rows = [
    [Paragraph('How buyer & seller meet', sTableCell), Paragraph('Buyer calls seller directly', sTableCellCenter), Paragraph('Buyer pays Jumia, never talks to seller', sTableCellCenter), Paragraph('<b><font color="#E5001A">Both</font></b> - depends on product', ParagraphStyle('AvCell', parent=sTableCellCenter, textColor=AVAZONIA_RED, fontName='Helvetica-Bold'))],
    [Paragraph('Who handles money?', sTableCell), Paragraph('They pay each other', sTableCellCenter), Paragraph('Jumia holds money', sTableCellCenter), Paragraph('<b>Avazonia holds for normal orders</b><br/>For cars/machines, they pay each other', ParagraphStyle('AvCell2', parent=sTableCellCenter, fontSize=7, leading=9))],
    [Paragraph('Who delivers?', sTableCell), Paragraph('Seller does', sTableCellCenter), Paragraph('Jumia does (or seller drops to hub)', sTableCellCenter), Paragraph('Seller drops to hub<br/><font size="6">Later: Avazonia Express</font>', sTableCellCenter)],
    [Paragraph('Cost to seller', sTableCell), Paragraph('Free to list<br/>Pay only to be seen', sTableCellCenter), Paragraph('Free to list<br/>5-20% when you sell', sTableCellCenter), Paragraph('<b>Free to list</b><br/>Commission only when you sell<br/>Boost to be seen (optional)', sTableCellCenter)],
    [Paragraph('Who can sell?', sTableCell), Paragraph('Anyone in 3 minutes', sTableCellCenter), Paragraph('After verification + training', sTableCellCenter), Paragraph('After verification<br/><font size="6">(we check you first)</font>', sTableCellCenter)],
    [Paragraph('Risk for buyer', sTableCell), Paragraph('Higher - no protection', sTableCellCenter), Paragraph('Lower - protected', sTableCellCenter), Paragraph('Low - protected for normal orders', sTableCellCenter)],
    [Paragraph('Best for', sTableCell), Paragraph('Cars, houses, used items', sTableCellCenter), Paragraph('New products, electronics, fashion', sTableCellCenter), Paragraph('Everything - we split by type', sTableCellCenter)],
]
table_data = [header] + rows
col_widths = [38*mm, 40*mm, 46*mm, 46*mm]
t_comp = Table(table_data, colWidths=col_widths, repeatRows=1)
style = TableStyle([
    ('BACKGROUND', (0,0), (-1,0), AVAZONIA_RED),
    ('TEXTCOLOR', (0,0), (-1,0), white),
    ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
    ('ALIGN', (0,0), (-1,0), 'CENTER'),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('GRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('ROWBACKGROUNDS', (0,1), (-1,-1), [white, LIGHT_BG]),
    ('LEFTPADDING', (0,0), (-1,-1), 5),
    ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ('TOPPADDING', (0,0), (-1,-1), 5),
    ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ('ROUNDEDCORNERS', [6,6,6,6]),
])
t_comp.setStyle(style)
# Highlight Avazonia column
for r in range(1, len(table_data)):
    t_comp.setStyle(TableStyle([('BACKGROUND', (-1,r), (-1,r), LIGHT_RED_BG)]))
story.append(t_comp)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("In short: <b>Jiji = cheap to start, risky to buy. Jumia = trusted to buy, expensive to sell.</b> Avazonia is <b>Jiji freedom + Jumia trust.</b>", ParagraphStyle('SummaryBox', parent=sBody, backColor=LIGHT_RED_BG, borderPadding=(6,8,6), borderColor=HexColor("#FECDD3"), borderWidth=0.5, textColor=DARK_GRAY, alignment=TA_CENTER)))

# PAGE 6: What this means for Avazonia
story.append(Paragraph("What This Means For Avazonia", sH1))
story.append(Paragraph("We will not copy one. We will combine - and add what neither has: <b>Avazonia own shop inside the mall.</b>", sBody))
# Hybrid diagram: Two paths
hybrid_data = [
    [Paragraph('<b><font color="#0A7F2E">PATH A - Pay on Avazonia</font></b><br/><font size="7" color="#374151">For phones, electronics, fashion, home - normal products</font><br/><br/><font size="8">Buyer pays Avazonia -> Avazonia delivers -> Avazonia pays seller (minus fee)<br/><b>Like Jumia. Trusted.</b></font>', ParagraphStyle('HybridA', parent=sBodySmall, alignment=TA_CENTER, leading=11)),
     Paragraph('<b><font color="#F27D26">PATH B - Talk to Seller</font></b><br/><font size="7" color="#374151">For cars, machines, big solar, services - expensive/custom</font><br/><br/><font size="8">Buyer clicks "Contact Vendor" -> talks directly -> they agree<br/><b>Like Jiji. Flexible.</b></font>', ParagraphStyle('HybridB', parent=sBodySmall, alignment=TA_CENTER, leading=11))],
]
t_hybrid = Table(hybrid_data, colWidths=[85*mm, 85*mm])
t_hybrid.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (0,0), LIGHT_GREEN_BG),
    ('BACKGROUND', (1,0), (1,0), LIGHT_ORANGE_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 10),
    ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ('TOPPADDING', (0,0), (-1,-1), 10),
    ('BOTTOMPADDING', (0,0), (-1,-1), 10),
    ('ROUNDEDCORNERS', [8,8,8,8]),
]))
story.append(t_hybrid)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("Admin decides which category uses which path. Example: <b>'Samsung Galaxy' -> Path A. '2020 Haval H6 - USD 6,600 FOB' -> Path B.</b> This is exactly what the client asked for in Section 23.", sBodySmall))
story.append(Paragraph("Why this is smarter than copying either:", sH2))
benefits = [
    [Paragraph('<b>For Customers</b>', ParagraphStyle('BenHead', parent=sH3, textColor=AVAZONIA_RED, alignment=TA_LEFT)), Paragraph('<b>For Sellers</b>', ParagraphStyle('BenHead2', parent=sH3, textColor=AVAZONIA_RED, alignment=TA_LEFT)), Paragraph('<b>For Avazonia</b>', ParagraphStyle('BenHead3', parent=sH3, textColor=AVAZONIA_RED, alignment=TA_LEFT))],
    [Paragraph('- One place for everything<br/>- Can compare Avazonia own products vs others<br/>- Protected when paying on site<br/>- Can negotiate for big items', sBodySmall), Paragraph('- Free to list (like Jiji)<br/>- Only pay when you sell (like Jumia)<br/>- Can still sell big items without platform handling money<br/>- Gets seen by many buyers', sBodySmall), Paragraph('- Earns commission (Path A)<br/>- Earns boost/lead fees (Path B)<br/>- No need to stock everything<br/>- Can still sell own stock for higher margin', sBodySmall)],
]
t_ben = Table(benefits, colWidths=[56*mm, 57*mm, 57*mm])
t_ben.setStyle(TableStyle([
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, white]),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_ben)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("Add the piece neither has well: <b>Avazonia Owned</b> - our own verified shop. Customers see <b>OK Avazonia Owned</b> badge and trust it more. We earn full margin there, and we learn what sells before inviting other sellers for that product.", ParagraphStyle('OwnedNote', parent=sBody, backColor=LIGHT_RED_BG, borderPadding=(6,8,6), fontName='Helvetica', textColor=DARK_GRAY)))
story.append(Paragraph("This is the 'mall with its own shop' - Jiji and Jumia do not have it. It is Avazonia advantage.", sBodySmall))

# PAGE 7: How Avazonia will make money (simple)
story.append(Paragraph("How Avazonia Will Make Money", sH1))
story.append(Paragraph("Simple rule: <b>Listing is free. You pay only when you earn.</b> This is what makes sellers join (Jiji lesson) and stay (Jumia lesson).", sBody))
# Revenue streams simple
rev_data = [
    [Paragraph('<b>1. Commission</b><br/><font size="7" color="#374151">When buyer pays on Avazonia<br/>5-20% by category<br/><font color="#6B7280">e.g. phone 6%, fashion 20%</font></font>', sTableCellCenter),
     Paragraph('<b>2. Boost</b><br/><font size="7" color="#374151">Seller pays to be seen at top<br/>for 3 days<br/><font color="#6B7280">Optional - like Jiji Top Ad</font></font>', sTableCellCenter),
     Paragraph('<b>3. Leads</b><br/><font size="7" color="#374151">For cars/houses, seller pays<br/>per serious buyer<br/><font color="#6B7280">GHS 30-100 per lead</font></font>', sTableCellCenter),
     Paragraph('<b>4. Own Sales</b><br/><font size="7" color="#374151">Avazonia own shop<br/>Keeps full profit<br/><font color="#6B7280">No commission - all ours</font></font>', sTableCellCenter)],
]
t_rev = Table(rev_data, colWidths=[42.5*mm, 42.5*mm, 42.5*mm, 42.5*mm])
t_rev.setStyle(TableStyle([
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_BG),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 8),
    ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_rev)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("What we will <b>not</b> do at launch (to keep it simple):", sH2))
not_do = [
    [Paragraph('No monthly rent - like Jumia, opening shop is free', ParagraphStyle('NotDo', parent=sBodySmall, leftIndent=12, bulletIndent=4, firstLineIndent=0)),
     Paragraph('No complicated warehouse - seller keeps stock at first (Dropship)', ParagraphStyle('NotDo2', parent=sBodySmall, leftIndent=12, bulletIndent=4)),
     Paragraph('No forcing sellers to use Avazonia delivery - they can use any courier, later we add Avazonia Express if needed', ParagraphStyle('NotDo3', parent=sBodySmall, leftIndent=12, bulletIndent=4))],
]
# Simpler as bullet list
story.append(Paragraph("- <b>No monthly shop rent</b> - opening a shop is free (like both Jiji and Jumia).", sBullet))
story.append(Paragraph("- <b>No warehouse needed at first</b> - seller keeps their stock, just brings package to hub when order comes (Dropship). Jumia Express warehouses come later if we need them.", sBullet))
story.append(Paragraph("- <b>No forcing</b> - seller can use any delivery. Later Avazonia can offer its own delivery as an option.", sBullet))
story.append(Paragraph("This keeps costs low for Avazonia and for sellers - exactly what made Jiji grow to 45M visits.", sBodySmall))
story.append(Spacer(1, 2*mm))
# Pricing example visual
story.append(Paragraph("Example: How seller sets price (so they do not lose money):", sH2))
story.append(Paragraph("If a dress seller wants <b>GHS 950</b> in her pocket, and commission is <b>20%</b> + <b>GHS 8</b> delivery, what should she list it for?", sBodySmall))
formula_data = [[
    Paragraph('<b><font color="#E5001A" size="10">( 950 + 8 ) ? ( 1 - 0.20 ) = GHS 1,198</font></b><br/><font size="7" color="#6B7280">Buyer pays 1,198 -> Avazonia keeps 248 -> seller gets 950 + 8 delivery<br/>We will give sellers a calculator - like Jumia does - so they never guess.</font>', ParagraphStyle('Formula', parent=sBodySmall, alignment=TA_CENTER, leading=12))
]]
t_formula = Table(formula_data, colWidths=[170*mm])
t_formula.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_RED_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#FECDD3")),
    ('ROUNDEDCORNERS', [8,8,8,8]),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 10),
    ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ('TOPPADDING', (0,0), (-1,-1), 10),
    ('BOTTOMPADDING', (0,0), (-1,-1), 10),
    ('ALIGN', (0,0), (-1,-1), 'CENTER'),
]))
story.append(t_formula)

# PAGE 8: Buyer/Seller Journey
story.append(Paragraph("What It Will Look Like", sH1))
story.append(Paragraph("Two simple journeys - no tech words.", sBody))
# Buyer journey
story.append(Paragraph("For a Buyer (customer)", sH2))
buyer_steps = [
    [Paragraph('<b>1</b>', ParagraphStyle('NumGreen', parent=sTableCellCenter, textColor=white, fontName='Helvetica-Bold', fontSize=14, backColor=GREEN, leading=14, borderPadding=(4,4,4))), Paragraph('<b>Finds product</b><br/><font size="7">Searches "Samsung phone"<br/>Sees Avazonia Owned + other sellers together</font>', sTableCell), Paragraph('<b>2</b>', ParagraphStyle('NumRed', parent=sTableCellCenter, textColor=white, fontName='Helvetica-Bold', fontSize=14, backColor=AVAZONIA_RED, leading=14)), Paragraph('<b>Chooses path</b><br/><font size="7">Cheap item -> "Add to Cart" (pays Avazonia)<br/>Car/machine -> "Contact Vendor" (chats)</font>', sTableCell)],
]
# Simpler as horizontal flow
buyer_flow = [
    [Paragraph('<b>Search</b><br/><font size="7">Finds what they want<br/>Compare prices</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>Choose</b><br/><font size="7">Add to cart <b>or</b><br/>Contact seller</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>Get it</b><br/><font size="7">Pay & delivered<br/>Or meet & pay</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>Review</b><br/><font size="7">Leaves stars<br/>Builds trust</font>', sTableCellCenter)],
]
t_buyer = Table(buyer_flow, colWidths=[28*mm, 10*mm, 28*mm, 10*mm, 28*mm, 10*mm, 28*mm])
t_buyer.setStyle(TableStyle([
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('BACKGROUND', (0,0), (0,0), LIGHT_BG),
    ('BACKGROUND', (2,0), (2,0), LIGHT_RED_BG),
    ('BACKGROUND', (4,0), (4,0), LIGHT_GREEN_BG),
    ('BACKGROUND', (6,0), (6,0), LIGHT_BG),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_buyer)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("For a Seller (shop owner)", sH2))
seller_flow = [
    [Paragraph('<b>Apply</b><br/><font size="7">Fill form<br/>Ghana Card + bank</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>Approved</b><br/><font size="7">We check you<br/>You get Verified badge</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>List</b><br/><font size="7">Upload products<br/>We approve</font>', sTableCellCenter),
     Paragraph('<font size="12">-></font>', sTableCellCenter),
     Paragraph('<b>Sell</b><br/><font size="7">Get orders<br/>Paid weekly</font>', sTableCellCenter)],
]
t_seller = Table(seller_flow, colWidths=[28*mm, 10*mm, 28*mm, 10*mm, 28*mm, 10*mm, 28*mm])
t_seller.setStyle(TableStyle([
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROWBACKGROUNDS', (0,0), (-1,-1), [LIGHT_BG]),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_seller)
story.append(Spacer(1, 3*mm))
story.append(Paragraph("Both sides are simple. No warehouse, no monthly fees, no complex training at launch. Just like Jiji ease, with Jumia trust where it matters.", sBodySmall))
story.append(Spacer(1, 2*mm))
# Trust badges visual
badges_data = [
    [Paragraph('<b><font color="#E5001A">OK Avazonia Owned</font></b><br/><font size="6" color="#6B7280">Sold by us<br/>Highest trust</font>', sTableCellCenter),
     Paragraph('<b><font color="#0A7F2E">OK Verified Vendor</font></b><br/><font size="6" color="#6B7280">Checked by us<br/>Trusted seller</font>', sTableCellCenter),
     Paragraph('<b>STAR  Avazonia Choice</b><br/><font size="6" color="#6B7280">Best products<br/>Chosen by us</font>', sTableCellCenter),
     Paragraph('<b>SHIELD  Buyer Protection</b><br/><font size="6" color="#6B7280">Money protected<br/>On Path A orders</font>', sTableCellCenter)],
]
t_badges = Table(badges_data, colWidths=[42.5*mm, 42.5*mm, 42.5*mm, 42.5*mm])
t_badges.setStyle(TableStyle([
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ('BACKGROUND', (0,0), (-1,-1), LIGHT_BG),
    ('LEFTPADDING', (0,0), (-1,-1), 6),
    ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ('TOPPADDING', (0,0), (-1,-1), 6),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ('ROUNDEDCORNERS', [6,6,6,6]),
]))
story.append(t_badges)

# PAGE 9: Next Steps
story.append(Paragraph("Next Steps - Simple Timeline", sH1))
story.append(Paragraph("We will not build everything at once. We start simple, like Jiji did - then add Jumia-like power as we grow.", sBody))
timeline = [
    [Paragraph('<b><font color="#E5001A">Phase 1 - Now</font></b><br/><font size="7" color="#111827">Weeks 1-6</font><br/><br/><font size="7">OK Categories & subcategories (done)<br/>OK Seller badges & Avazonia Owned<br/>OK Search & filters<br/>OK Manual seller creation (admin adds sellers)</font><br/><br/><font size="6" color="#0A7F2E"><b>You see:</b> Mall looks real, trust badges work, no self-registration yet</font>', sTableCell),
     Paragraph('<b>Phase 2 - Soon</b><br/><font size="7" color="#111827">Weeks 6-14</font><br/><br/><font size="7">- Sellers apply & get Verified<br/>- Sellers upload products<br/>- Orders split by seller<br/>- Weekly payouts<br/>- Contact Vendor for cars/machines</font><br/><br/><font size="6" color="#F27D26"><b>You see:</b> Real sellers, real money flow</font>', sTableCell),
     Paragraph('<b>Phase 3 - Later</b><br/><font size="7" color="#111827">When needed</font><br/><br/><font size="7">- Sponsored products (pay to boost)<br/>- Avazonia Express delivery<br/>- Full promotions (Black Friday)<br/>- Reports & analytics</font><br/><br/><font size="6" color="#6B7280"><b>You see:</b> Scale - like Jumia at full power</font>', sTableCell)],
]
t_timeline = Table(timeline, colWidths=[56*mm, 57*mm, 57*mm])
t_timeline.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (0,0), LIGHT_RED_BG),
    ('BACKGROUND', (1,0), (1,0), LIGHT_ORANGE_BG),
    ('BACKGROUND', (2,0), (2,0), LIGHT_BG),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('INNERGRID', (0,0), (-1,-1), 0.5, HexColor("#E5E7EB")),
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ('LEFTPADDING', (0,0), (-1,-1), 8),
    ('RIGHTPADDING', (0,0), (-1,-1), 8),
    ('TOPPADDING', (0,0), (-1,-1), 8),
    ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ('ROUNDEDCORNERS', [8,8,8,8]),
]))
story.append(t_timeline)
story.append(Spacer(1, 4*mm))
# Recommendation box
rec = [[
    Paragraph('<b><font color="#111827">Our recommendation for Avazonia</font></b><br/><br/><font size="8">Start like <b><font color="#0A7F2E">Jiji</font></b> (free to list, easy to join) - this brings sellers quickly.<br/>Operate like <b><font color="#F27D26">Jumia</font></b> where money is involved (hold money, weekly payout, commission) - this brings buyer trust.<br/>Keep <b><font color="#E5001A">Avazonia Owned</font></b> as your special advantage - neither Jiji nor Jumia has this. It lets you make full profit on key products and learn the market first.</font><br/><br/><font size="7" color="#6B7280">This is the lowest risk, highest trust way. We have already built the foundation: SQLite fallback, category drill-down, seller structure ready.</font>', sBodySmall)
]]
t_rec = Table(rec, colWidths=[170*mm])
t_rec.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,-1), HexColor("#F0F9FF")),
    ('BOX', (0,0), (-1,-1), 0.5, HexColor("#BFDBFE")),
    ('ROUNDEDCORNERS', [8,8,8,8]),
    ('LEFTPADDING', (0,0), (-1,-1), 10),
    ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ('TOPPADDING', (0,0), (-1,-1), 10),
    ('BOTTOMPADDING', (0,0), (-1,-1), 10),
]))
story.append(t_rec)
story.append(Spacer(1, 6*mm))
story.append(Paragraph("Questions? We can walk through this in 15 minutes - no tech talk, just business.", ParagraphStyle('FinalNote', parent=sBody, alignment=TA_CENTER, textColor=MID_GRAY, fontName='Helvetica-Oblique', fontSize=9)))
story.append(Spacer(1, 2*mm))
story.append(Paragraph("- Avazonia Tech Team - August 2026", ParagraphStyle('Sign', parent=sBodySmall, alignment=TA_CENTER, textColor=AVAZONIA_RED, fontName='Helvetica-Bold')))

# Build
doc = SimpleDocTemplate(
    OUTPUT,
    pagesize=A4,
    leftMargin=15*mm,
    rightMargin=15*mm,
    topMargin=16*mm,
    bottomMargin=16*mm,
    title="Avazonia - Jiji vs Jumia Strategic Comparison",
    author="Avazonia",
    subject="Strategic Comparison",
    keywords="Avazonia, Jiji, Jumia, marketplace",
)

# Use custom cover handling: first page different
def on_first_page(canvas, doc):
    cover_header_footer(canvas, doc)

def on_later_pages(canvas, doc):
    header_footer(canvas, doc)

# We need to handle cover separately - build with onFirstPage
# Create a wrapper: use SimpleDocTemplate with two callbacks
# ReportLab supports onFirstPage / onLaterPages
doc.build(story, onFirstPage=on_first_page, onLaterPages=on_later_pages)
print(f"PDF generated: {OUTPUT}")
print(f"Size: {os.path.getsize(OUTPUT)} bytes")
