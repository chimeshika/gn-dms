const paths = {
  dashboard: "M3 10 12 3l9 7M5 9v12h5v-7h4v7h5V9",
  officers:
    "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8m1 3a4 4 0 0 1 4 4v2",
  documents: "M14 2H5v20h14V7ZM14 2v6h5M8 12h8M8 16h8",
  letters: "M3 5h18v14H3ZM3 5l9 8 9-8",
  reports: "M4 21V11h3v10M10 21V3h3v18M16 21V7h3v14",
  users: "M20 21v-2a7 7 0 0 0-14 0v2M13 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8",
  "audit-logs": "M12 8v5l4 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0",
  settings:
    "M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2",
  search: "M21 21l-6-6M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0",
  bell: "M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4",
  menu: "M3 6h18M3 12h18M3 18h18",
  plus: "M12 5v14M5 12h14",
  upload: "M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5",
  shield: "M12 2 3 6v7c0 5 9 9 9 9s9-4 9-9V6ZM8 12l3 3 5-6",
};
export default function Icon({ name, size = 21 }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={paths[name] || paths.documents} />
    </svg>
  );
}
