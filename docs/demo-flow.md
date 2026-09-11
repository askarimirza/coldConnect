# ColdConnect: 3-Minute Live Hackathon Demo Script

Use this script during your presentation to judges at **KALPVRUKSH 2.0 Mini Hackathon**.

---

## 🕒 Minute 0:00 - 0:45 | The Hook & Problem Statement (P21)

> **Speaker 1:**  
> *"Respected judges, post-harvest losses in India destroy over 30 to 40% of our vegetable and fruit harvest every single year. When tomato or onion prices crash at the mandi, why do farmers dump produce on the highway? Because they have **zero visibility** into nearby cold storage facilities.*  
> *They don't know who has empty space, whether the temperature is suitable for tomatoes, how much it costs, or if they will be turned away after traveling 30 kilometers.*  
> *This is Problem Statement P21. And our working solution is **ColdConnect: Smart Cold Storage Finder & Booking System**."*

---

## 🕒 Minute 0:45 - 1:45 | Farmer Discovery & Transparent Smart Match

> **Speaker 2:** *(Shows screen at `http://localhost/coldconnect/`)*  
> *"Let's take a real-world scenario. Farmer Ramesh Patel in Ahmedabad has harvested **500 kg of fresh tomatoes** and needs to store them for **15 days** until mandi prices recover.*  
> *Ramesh visits ColdConnect and enters: **Tomato, 500 kg, 15 days, Ahmedabad**.*  
> *(Clicks 'Find Best Storage')*  
>  
> *Immediately, ColdConnect scans registered facilities. Only storages with at least 500 kg available and certified for tomatoes appear.*  
> *Notice our top card: **Shree Cold Storage** has a **96% Smart Match Score** with our **BEST MATCH** badge.*  
> *Why 96%? It is not a random number or a fake AI claim. It is our transparent rule-based algorithm:  
> - **Distance (30%):** Just ~3.5 km away  
> - **Price (25%):** Ultra-competitive at ₹2.00/kg/day  
> - **Capacity Buffer (20%):** 800 kg available for his 500 kg request  
> - **Crop & Temperature (25%):** Chamber operates at 2°C - 8°C, which prevents tomato rotting.*  
>  
> *Furthermore, our dynamic calculator shows exact rental cost upfront: **500 kg × ₹2.00 × 15 days = ₹15,000**. No hidden fees."*

---

## 🕒 Minute 1:45 - 2:30 | Booking Submission & Live Owner Confirmation

> **Speaker 1:** *(Clicks 'Book Storage Now' & submits request)*  
> *"Ramesh clicks 'Book Storage' and confirms his reservation. His booking is registered with status **Pending**.*  
>  
> *Now, let's switch to the other side: **Haresh Shah, owner of Shree Cold Storage**.*  
> *(Logs in as `owner@coldconnect.test`)*  
>  
> *In Haresh's Owner Portal, he sees:  
> - Total capacity: 2000 kg  
> - Currently available: **800 kg**  
> - A new reservation request from Ramesh Patel for 500 kg of tomatoes!*  
>  
> *Haresh reviews the request and clicks **Accept**.*  
> *(Clicks Accept button)*  
>  
> *Instantly, our backend transaction updates the booking to **Accepted** and reduces Shree Cold Storage's free capacity from **800 kg to 300 kg** in real-time. Other farmers searching right now will immediately see the updated capacity."*

---

## 🕒 Minute 2:30 - 3:00 | Summary & Impact

> **Speaker 2:**  
> *"When Ramesh checks his **My Bookings** page, he sees his reservation confirmed with the facility's direct phone number.*  
>  
> *ColdConnect is:  
> 1. **Completely functional locally** with zero external API dependencies.  
> 2. **Built using native PHP 8.2 & MySQL**, making it lightweight and rural-ready.  
> 3. **Honest & Transparent:** Rule-based matching with zero deceptive claims.  
>  
> *ColdConnect turns perishable harvests into preserved profits. Thank you!"*
