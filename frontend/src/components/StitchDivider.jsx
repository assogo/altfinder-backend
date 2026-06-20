export default function StitchDivider({ className = '' }) {
  return (
    <div className={`stitch-divider ${className}`} aria-hidden="true">
      {Array.from({ length: 20 }).map((_, i) => (
        <span key={i} className={i % 2 === 0 ? 'stitch stitch-a' : 'stitch stitch-b'} />
      ))}
    </div>
  );
}
