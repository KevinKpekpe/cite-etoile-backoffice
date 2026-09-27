# Frais connexes — feuille de route

- [x] Lire les consignes du dépôt et vérifier l’état Git ; `.ai/rules` est absent.
- [x] Examiner les parcours existants : formules et souscriptions, contrats, paiements/affectations, échéances, reçus et portail client.
- [x] Repérer les décisions métier qui ne peuvent pas être déduites de l’implémentation existante.
- [x] Valider avec le métier : cadastre/aménagement au contrat signé, bornage à son marquage « réalisé », choix total/mensuel à la souscription et paiements séparés avec reçu.
- [x] Confirmer qu’aucun parcours de prise de possession n’existe dans le dépôt.
- [ ] Clarifier le jalon de prise de possession avant de brancher le blocage lié au bornage.
- [x] Concevoir le stockage des frais/échéances et leur intégration séparée avec les paiements et reçus, sans modifier le solde contractuel principal.
- [x] Implémenter la génération des frais de bornage, cadastre et aménagement selon les règles validées.
- [x] Ajouter les vues et accès portail client, avec filtrage par client et téléchargement des reçus.
- [x] Ajouter les tests de régression sur les montants, échéances, paiements, reçus et isolation client.
- [x] Lancer les tests ciblés (47 tests, 211 assertions), formater les fichiers PHP avec Pint et relire le diff.


## Hypothèses prudentes retenues

- Le paiement total d’aménagement est dû un mois après la signature, comme la première mensualité du mode mensuel.
- Les échéances créées à la signature sont des instantanés : une modification ultérieure du contrat ne les régénère pas et une annulation ne supprime pas l’historique financier.
- Les règles globales déjà configurées sur les paiements partiels et anticipés s’appliquent aussi à chaque frais connexe.
