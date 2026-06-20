export default function Pagination({ page, totalPages, onChange }) {
  if (totalPages <= 1) return null;
  return (
    <nav className="pagination" aria-label="Pagination">
      <button onClick={() => onChange(page - 1)} disabled={page <= 1}>← Précédent</button>
      <span className="pagination-info">Page {page} / {totalPages}</span>
      <button onClick={() => onChange(page + 1)} disabled={page >= totalPages}>Suivant →</button>
    </nav>
  );
}
