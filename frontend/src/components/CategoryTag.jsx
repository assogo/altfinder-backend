const LABELS = {
  developpeur: 'Développeur',
  marketing: 'Marketing',
  design: 'Design',
  autre: 'Autre',
};

export default function CategoryTag({ category }) {
  const key = LABELS[category] ? category : 'autre';
  return <span className={`tag tag-${key}`}>{LABELS[key]}</span>;
}
