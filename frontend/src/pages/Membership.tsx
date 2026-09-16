import { useState } from "react";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { SectionHeader } from "../components/ui/SectionHeader";
import { PricingCard } from "../components/cards/PricingCard";
import { BillingToggle } from "../components/ui/BillingToggle";
import { Reveal } from "../components/ui/Reveal";
import { membershipPlans } from "../data/memberships";
import { IMG, unsplash } from "../data/images";

const faqs = [
  { q: "Can I cancel anytime?", a: "Yes — all memberships are month-to-month with no long-term contracts required." },
  { q: "Is there a joining fee?", a: "No hidden fees. The price you see is the price you pay." },
  { q: "Can I freeze my membership?", a: "Yes, members can freeze their plan for up to 60 days per year." },
  { q: "Do you offer family plans?", a: "Yes, ask our front desk about our multi-member discount packages." },
];

export default function Membership() {
  const [yearly, setYearly] = useState(false);

  return (
    <>
      <PageHero
        eyebrow="Membership"
        title="Invest In Yourself"
        description="Premium plans built for every level of commitment — transparent pricing, zero hidden fees."
        image={unsplash(IMG.dumbbellReachHand, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <SectionHeader eyebrow="Pricing" title="Choose Your Plan" className="mb-10" />
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

      <section className="bg-charcoal py-24 lg:py-32">
        <Container>
          <SectionHeader eyebrow="FAQ" title="Common Questions" className="mb-14" />
          <div className="mx-auto grid max-w-3xl grid-cols-1 gap-4">
            {faqs.map((faq, i) => (
              <Reveal key={faq.q} delay={i * 0.06}>
                <div className="glass rounded-2xl p-6">
                  <h3 className="mb-2 text-sm font-semibold text-white">{faq.q}</h3>
                  <p className="text-sm text-white/55">{faq.a}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
    </>
  );
}
