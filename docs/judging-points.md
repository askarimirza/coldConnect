# ColdConnect: Judging Points & Evaluation Rubric Alignment

Designed for the judging criteria of **KALPVRUKSH 2.0 Mini Hackathon at Silver Oak University**.

---

## 1. Problem Relevance & Impact (Weight: High)
- **Direct Alignment with P21:** Directly addresses the lack of information on capacity, temperature, pricing, and distance that forces farmers into distress selling.
- **Economic Realities:** Farmers in Gujarat frequently face post-harvest gluts where crop prices crash to ₹1-2/kg. Being able to secure 10-20 days of refrigerated storage allows them to sell during market recovery, increasing realized income by 300–500%.
- **Spoilage Mitigation:** Directly prevents food waste by matching perishable crops to appropriate chamber temperatures before decomposition begins.

---

## 2. Technical Execution & Code Quality
- **Full-Stack Working MVP:** Not static mockups or slides. Complete database schema, prepared statement queries, session authentication, and transactional state management.
- **Zero Heavy Framework Bloat:** Runs natively on vanilla PHP 8.2 + MySQL on XAMPP. Ultra-low latency and minimal resource consumption.
- **Security by Default:** Bcrypt password hashing (`password_hash()`), parameterized PDO queries (SQLi protection), HTML escaping (`htmlspecialchars()` for XSS protection), role-based session isolation.
- **Resilient Capacity Management:** Prevents over-booking race conditions through transaction-based capacity decrement on owner acceptance.

---

## 3. Innovation & Smart Match Algorithm
- **Transparent Rule-Based Scoring:** Rather than misleading judges with blackbox "AI/ML" buzzwords, ColdConnect implements an auditable 100-point algorithm:
  - Distance: 30 pts
  - Market Price: 25 pts
  - Capacity Safety Buffer: 20 pts
  - Crop Compatibility: 15 pts
  - Temperature Suitability: 10 pts
- **No External Paid API Lock-In:** Uses a deterministic locality proximity matrix for the Ahmedabad region, eliminating costly Google Maps or third-party API dependencies.

---

## 4. User Experience & Design
- **AgriTech + Refrigeration Aesthetic:** Emerald forest greens paired with crisp arctic cyan and clean slate cards.
- **Immediate Value Feedback:** Clear "BEST MATCH" ribbon, dynamic live rental cost calculator, visual capacity progress meters.
- **Fast Judge Demonstration Mode:** Built-in "Load Judge Demo" and quick login switcher buttons allow judges to test the entire ecosystem in under 60 seconds without typing repetitive test data.
- **Responsive on All Viewports:** Works smoothly across smartphones, tablets, and laptops.

---

## 5. Feasibility & Scope Discipline
- **Disciplined MVP Scope:** Deliberately avoids fragile external APIs (payment gateways, OTP SMS, paid maps) that fail during offline hackathon judging sessions.
- **Practical Deployability:** Can be hosted on simple Linux or Windows shared hosting across cooperative banks, APMCs, and Farmer Producer Organizations (FPOs).
