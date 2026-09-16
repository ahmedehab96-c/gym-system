import { useState } from "react";
import { membershipPlans } from "../../data/memberships";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { PricingCard } from "../cards/PricingCard";
import { BillingToggle } from "../ui/BillingToggle";

export function MembershipPreview() {
  const [yearly, setYearly] = useState(false);

  return (
    <section className="py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="Membership Plans"
          title="Choose Your Path"
          description="Transparent pricing, no hidden fees. Cancel or upgrade anytime."
          className="mb-10"
        />
        <div className="mb-14 flex justify-center">
          <BillingToggle yearly={yearly} onChange={setYearly} />
        </div>
        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
          {membershipPlans.map((plan, i) => (
            <PricingCard key={plan.id} plan={plan} yearly={yearly} index={i} />
          ))}
        </div>
      </Container>
    </section>
  );
}
