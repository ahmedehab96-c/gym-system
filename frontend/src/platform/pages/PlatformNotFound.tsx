import { Link } from "react-router-dom";
import { Button } from "../../admin/components/ui/Button";

export default function PlatformNotFound() {
  return (
    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
      <p className="text-6xl font-bold text-sky-500">404</p>
      <p className="text-a-muted dark:text-a-dark-muted">This platform admin page doesn't exist.</p>
      <Link to="/platform/dashboard">
        <Button>Back to Dashboard</Button>
      </Link>
    </div>
  );
}
