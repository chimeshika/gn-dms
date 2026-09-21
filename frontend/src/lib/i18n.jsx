import { createContext, useContext, useState } from "react";
const translations = {
  en: {
    dashboard: "Dashboard",
    officers: "Officers",
    documents: "Documents",
    letters: "Letters",
    reports: "Reports",
    users: "Users",
    "audit-logs": "Audit Logs",
    settings: "Settings",
  },
  si: {
    dashboard: "උපකරණ පුවරුව",
    officers: "නිලධාරීන්",
    documents: "ලේඛන",
    letters: "ලිපි",
    reports: "වාර්තා",
    users: "පරිශීලකයන්",
    "audit-logs": "විගණන සටහන්",
    settings: "සැකසුම්",
  },
  ta: {
    dashboard: "முகப்பு",
    officers: "அலுவலர்கள்",
    documents: "ஆவணங்கள்",
    letters: "கடிதங்கள்",
    reports: "அறிக்கைகள்",
    users: "பயனர்கள்",
    "audit-logs": "தணிக்கை பதிவுகள்",
    settings: "அமைப்புகள்",
  },
};
const Context = createContext();
export function LanguageProvider({ children }) {
  const [language, setLanguage] = useState("en");
  return (
    <Context.Provider
      value={{
        language,
        setLanguage,
        t: (key, fallback) =>
          translations[language][key] ||
          translations.en[key] ||
          fallback ||
          key,
      }}
    >
      {children}
    </Context.Provider>
  );
}
export const useLanguage = () => useContext(Context);
export function LanguageSelect() {
  const { language, setLanguage } = useLanguage();
  return (
    <select
      className="language-select"
      aria-label="Interface language"
      value={language}
      onChange={(e) => setLanguage(e.target.value)}
    >
      <option value="en">EN · English</option>
      <option value="si">සිං · සිංහල</option>
      <option value="ta">தமிழ்</option>
    </select>
  );
}
