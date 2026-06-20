import { useEffect, useState, useCallback } from 'react';
import { fetchJobs } from '../api/jobs';
import JobCard from '../components/JobCard';
import Pagination from '../components/Pagination';
import StitchDivider from '../components/StitchDivider';

const CATEGORIES = [
  { value: '', label: 'Toutes' },
  { value: 'developpeur', label: 'Développeur' },
  { value: 'marketing', label: 'Marketing' },
  { value: 'design', label: 'Design' },
  { value: 'autre', label: 'Autre' },
];

export default function JobList() {
  const [keyword, setKeyword] = useState('');
  const [category, setCategory] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState({ data: [], meta: { total: 0, totalPages: 1 } });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(() => {
    setLoading(true);
    setError(null);
    fetchJobs({ keyword, category, page, limit: 20 })
      .then(setData)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [keyword, category, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [keyword, category]);

  return (
    <div className="page">
      <header className="topbar">
        <div className="brand">
          <span className="brand-alt">Alt</span>
          <span className="brand-slash">/</span>
          <span className="brand-finder">Finder</span>
        </div>
        <form className="search-form" onSubmit={(e) => { e.preventDefault(); load(); }}>
          <input
            type="search"
            placeholder="Rechercher un poste, une techno, une ville…"
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
          />
          <button type="submit">Rechercher</button>
        </form>
      </header>

      <StitchDivider />

      <div className="chips">
        {CATEGORIES.map((c) => (
          <button
            key={c.value}
            type="button"
            className={`chip ${category === c.value ? 'chip-active' : ''}`}
            onClick={() => setCategory(c.value)}
          >
            {c.label}
          </button>
        ))}
      </div>

      <p className="result-count">
        {loading ? 'Recherche en cours…' : `${data.meta.total} offre${data.meta.total > 1 ? 's' : ''} en alternance`}
      </p>

      {error && <p className="error-box">Impossible de charger les offres : {error}</p>}

      {!loading && !error && data.data.length === 0 && (
        <div className="empty-state">
          <p>Aucune offre ne correspond à ta recherche.</p>
          <p>Essaie un autre mot-clé ou change de catégorie.</p>
        </div>
      )}

      <div className="job-grid">
        {data.data.map((job) => (
          <JobCard key={job.id} job={job} />
        ))}
      </div>

      <Pagination page={page} totalPages={data.meta.totalPages} onChange={setPage} />
    </div>
  );
}
