# 📚 Werkinstructies voor lokale ontwikkeling (Git + Composer)

Dit project gebruikt GitHub als centrale broncodebeheer, Composer voor packagebeheer en branches voor het ontwikkelen van nieuwe functionaliteit.  
Volg deze regels om stabiel en gestructureerd te werken.

---

## 🔧 Eerste keer lokaal opzetten

1. **Clone de repository** (via GitHub Desktop of GitKraken)
2. **Ga naar de juiste branch** (bv. `development`, `feature/...`)
3. **Installeer dependencies** met Composer:
   ```bash
   composer install
   ```
   > Dit genereert lokaal de `vendor/` map op basis van `composer.lock`

---

## 🔁 Regels voor werken met branches

### 📥 Pullen (updates binnenhalen)
- Open GitHub Desktop of GitKraken
- Selecteer de juiste branch (bv. `development` of jouw feature branch)
- **Klik op "Pull"** (GitHub Desktop) of **"Fetch/Pull"** (GitKraken)

### 📤 Pushen (jouw wijzigingen uploaden)
1. Commit je wijzigingen lokaal in GitHub Desktop of GitKraken
2. Klik op **"Push origin"** (GitHub Desktop) of **"Push"** (GitKraken)

### 🔄 Branch updaten met nieuwste wijzigingen (merge main/development in jouw branch)
1. In GitHub Desktop:  
   - Ga naar jouw branch
   - Menu → Branch → *Update from main/development*
2. In GitKraken:
   - Checkout je feature branch
   - Klik & drag `development` naar jouw branch → Kies **"Merge development into ..."**

---

## 📦 Composer-richtlijnen

- Wijzig je een package (toevoegen/verwijderen)?  
  Dan **altijd beide bestanden committen**:
  - `composer.json`
  - `composer.lock`

> Hierdoor draaien alle omgevingen (lokaal, test, productie) exact dezelfde versies.

### ❌ Niet committen:
- `vendor/` map
- `.env` bestand

---

## 🧠 Handige Git-conventies

- Prefix branches: `feature/...`, `bugfix/...`, `hotfix/...`
- Kleine, afgebakende commits
- Altijd testen voor je pushed
- Werk in een eigen branch, nooit direct op `main` of `production`

---

## 📁 Bestandsoverzicht

| Bestand           | Uitleg                                    |
|-------------------|-------------------------------------------|
| `.gitignore`       | Bepaalt wat **niet** wordt meegestuurd   |
| `composer.json`    | Welke packages zijn nodig                |
| `composer.lock`    | Exacte versies van die packages          |
| `.env`             | Bevat geheime/omgeving-specifieke data (niet committen) |
| `/vendor/`         | Bevat de gedownloade packages (lokaal gegenereerd) |

---

## 🤝 Hulp nodig?
Vraag in het team of check de GitHub projectpagina.

---