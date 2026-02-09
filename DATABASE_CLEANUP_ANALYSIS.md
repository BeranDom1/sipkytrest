# 🗑️ Analýza nepoužívaných tabulek v databázi

**Datum analýzy:** 9. února 2026  
**Verze projektu:** liga-app (produkční verze)

---

## 📊 Souhrn

Z celkem **18 tabulek** v databázi `d377108_liga.sql` jsou **3 tabulky zcela nepoužívané** a **2 tabulky částečně nepoužívané**. Jejich smazáním se ušetří úložný prostor a zjednoduší se správa databáze.

---

## 🔴 ZCELA NEPOUŽÍVANÉ TABULKY (Vhodné ke smazání)

### 1. `backup_hraci_unikatni_jmena`
**Status:** ✗ **Nepoužívá se v projektu**

**Popis:**
- Tabulka obsahující jedinečná jména hráčů (přibližně 47 záznamů)
- Vypadá jako stará záloha/backup (název s prefixem `backup_`)
- Není třídí v žádném PHP souboru projektu

**Dopad smazání:**
- ✅ **BEZPEČNÉ** - žádná funkčnost nezávisí na této tabulce
- ✅ Ušetří cca 5 KB úložného prostoru

**Doporučení:** ✅ **SMAZAT**

---

### 2. `hraci_unikatni_jmena_tmp`
**Status:** ✗ **Nepoužívá se v projektu**

**Popis:**
- Tabulka s prefixem `_tmp` (dočasná tabulka)
- Obsahuje 47 záznamů - pravděpodobně z migrace dat
- Nebyla nikdy smázána ze schématu
- Nikdy se nepoužívá v PHP kódu

**Dopad smazání:**
- ✅ **BEZPEČNÉ** - dočasná tabulka z procesu migrace
- ✅ Ušetří cca 5 KB úložného prostoru

**Doporučení:** ✅ **SMAZAT**

---

### 3. `seznam_hracu_web`
**Status:** ✗ **Nepoužívá se v projektu**

**Popis:**
- Tabulka s názvem `seznam_hracu_web` (seznam hráčů pro web)
- Obsahuje statické údaje: jméno, přezdívka, bydliště, věk
- Existuje zde **60 unikátních hráčů** s doplňkovými informacemi
- **NENÍ PŘÍPAD ŽÁDNÝ ODKAZ** v PHP kódu

**Použití v databázi:**
```
- Žádný SELECT `seznam_hracu_web`
- Žádný INSERT `seznam_hracu_web`
- Žádný JOIN na `seznam_hracu_web`
```

**Alternativa:**
- Projekt používá `hraci_unikatni_jmena` a `hraci` jako primární zdroje jmen hráčů
- Údaje o věku/bydlišti se nepoužívají v aplikaci

**Dopad smazání:**
- ✅ **BEZPEČNÉ** - nezávisí na ní žádná funkcionalita
- ✅ Ušetří cca 8 KB úložného prostoru

**Doporučení:** ✅ **SMAZAT**

---

## 🟡 ČÁSTEČNĚ NEPOUŽÍVANÉ TABULKY

### 4. `rezervace_old`
**Status:** ⚠️ **Nepoužívá se, ale může obsahovat archivní data**

**Popis:**
- Tabulka s prefixem `_old` (stará verze)
- Schéma se liší od aktuální `rezervace`: 
  - Stará verze: `terc_id, jmeno, datum, cas`
  - Nová verze: `datum, hodina, terc, jmeno`
- Obsahuje cca 250 starých rezervací (z 2025)
- Není nikdy čtena v PHP kódu

**Aktivní tabulka:**
- Projekt používá `rezervace` (bezpečnější schéma)

**Dopad smazání:**
- ⚠️ **ČÁSTEČNĚ BEZPEČNÉ** - jsou zde archivní data
- Ušetří cca 15 KB

**Doporučení:** 
- 📌 Pokud není potřeba archiv -> **SMAZAT**
- 🔒 Pokud je archiv důležitý -> **PONECHAT** a eventuálně přejmenovat na `rezervace_archive`

---

### 5. `n_ligy`
**Status:** ⚠️ **Používá se pouze v experimentálním kódu (liga-app-clean)**

**Popis:**
- Tabulka obsahující ligy pro ročník se schématem: `rocnik_id, kod, nazev, poradi`
- V databázi je **6 záznamů** pro ročník 4 (Jaro 2026)
- Odkaz: `/liga-app-clean/admin/generate_n_schedule.php` (POUZE v čisté variantě)

**Aktivní v projektu:**
- ❌ **NE** v produkční `liga-app`
- ✅ **ANO** v experimentální `liga-app-clean`

**Dopad smazání:**
- ⚠️ Skončí funkcionalita v `liga-app-clean/admin/generate_n_schedule.php`
- Produkční kód (`liga-app`) to neovlivní

**Doporučení:**
- 📌 Pokud chcete `liga-app-clean` opravdu experimentální -> **SMAZAT**
- 🔒 Pokud plánujete migraci na nové schéma -> **PONECHAT**

---

## ✅ AKTIVNĚ POUŽÍVANÉ TABULKY (Nikdy nesmazávat)

| Tabulka | Využití | Důležitost |
|---------|---------|-----------|
| `hraci_unikatni_jmena` | JOIN v zapasech, rozpisu, statistikách | 🔴 KRITICKÁ |
| `hraci_v_sezone` | Seznam hráčů v sezóně/ligě | 🔴 KRITICKÁ |
| `zapasy` | Veškerá data zápasů (skóre, statistiky) | 🔴 KRITICKÁ |
| `rocniky` | Přepínač ročníků v SESSION | 🔴 KRITICKÁ |
| `ligy` | Primární seznam lig (0-5) | 🔴 KRITICKÁ |
| `ligy_nazvy` | Vlastní názvy lig ve výpisu | 🟡 DŮLEŽITÁ |
| `ligy_loga` | Loga sponzorů v UI | 🟡 DŮLEŽITÁ |
| `uzivatele` | Autentizace a autorizace | 🔴 KRITICKÁ |
| `admins` | Fallback pro staré admin účty | 🟡 DŮLEŽITÁ |
| `rezervace` | Rezervace terčů (aktivní) | 🟡 DŮLEŽITÁ |
| `prezidentsky_turnaj` | Data Prezidentského poháru | 🟡 DŮLEŽITÁ |
| `prezidentsky_zapas` | Zápasy poháru | 🟡 DŮLEŽITÁ |
| `turnaje` | Nový systém turnajů | 🟡 DŮLEŽITÁ |
| `turnaj_hraci` | Hráči v turnajích | 🟡 DŮLEŽITÁ |
| `turnaj_zapasy` | Zápasy v turnajích | 🟡 DŮLEŽITÁ |
| `hraci` | Fallback jména (legacy) | 🟡 DŮLEŽITÁ |

---

## 🧹 Doporučený plán čištění

### ✅ BEZPEČNÉ SMAZÁNÍ (bez vlivu na funkcionalitu)
```sql
-- 1. Smazat stará/dočasná schémata
DROP TABLE IF EXISTS `backup_hraci_unikatni_jmena`;
DROP TABLE IF EXISTS `hraci_unikatni_jmena_tmp`;
DROP TABLE IF EXISTS `seznam_hracu_web`;

-- Ušetřeno: ~18 KB
```

### ⚠️ VOLITELNÉ SMAZÁNÍ (má závislosti)
```sql
-- Pouze pokud jste si jistí, že nebudete archivovat staré rezervace
-- DROP TABLE IF EXISTS `rezervace_old`;

-- Pouze pokud neplánujete migraci na n_ligy schéma
-- DROP TABLE IF EXISTS `n_ligy`;
```

---

## 📋 Manuální ověření

**Aby se zajistilo, že jsou tabulky skutečně nepoužívané, zkontrolujte:**

1. ✅ Grep výsledky potvrzují: `grep -r "seznam_hracu_web\|hraci_unikatni_jmena_tmp\|backup_" liga-app/ --include="*.php"`
2. ✅ Bez AJAX volání: Zkontrolujte `liga-app/assets/*.js` pro AJAX dotazy
3. ✅ Bez externích skriptů: Žádné cron joby nečtoucí z těchto tabulek

---

## 💾 Jak vytvořit backup před smazáním

```bash
# Vyzkoušejte na lokální kopii před WEDOS
mysqldump -h localhost -u user -p d377108_liga \
  backup_hraci_unikatni_jmena \
  hraci_unikatni_jmena_tmp \
  seznam_hracu_web \
  > nepouživane_tabulky_backup.sql

# Poté smazat
mysql -h localhost -u user -p d377108_liga < drop_tables.sql
```

---

## 📝 Poznámky

- **Projekt je v aktivním vývoji** - před rusením tabulek doporučuji vytvořit backup
- **Dva varianty kódu**: `liga-app` (produkce) a `liga-app-clean` (reference) mají mírně odlišné schéma
- **WEDOS hosting** - před smazáním na WEDOS, otestujte na lokálním serveru
